<?php

declare(strict_types=1);

namespace Nexum\Security\Passkey\Authentication;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Nexum\Security\Authentication\AccessTokenManager;
use Nexum\Security\Authentication\IssuedAccessToken;
use Nexum\Security\Contract\SubjectUserInterface;
use Nexum\Security\Passkey\Entity\PasskeyChallenge;
use Nexum\Security\Passkey\Entity\PasskeyCredential;
use Psr\Clock\ClockInterface;
use Symfony\Component\Uid\Uuid;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

final readonly class PasskeyManager
{
    public function __construct(
        private ManagerRegistry $registry,
        private WebauthnServer $server,
        private ClockInterface $clock,
        private AccessTokenManager $tokens,
        private int $challengeTtl,
        private int $maxAttempts,
    ) {
    }

    /** @return array{challenge: string, publicKey: array<string, mixed>} */
    public function registrationOptions(SubjectUserInterface $user): array
    {
        $credentials = $this->credentialsFor($user);
        $options = PublicKeyCredentialCreationOptions::create(
            PublicKeyCredentialRpEntity::create('', $this->server->rpId()),
            PublicKeyCredentialUserEntity::create(
                $user->getUserIdentifier(),
                $this->userHandle($user),
                $user->getUserIdentifier(),
            ),
            random_bytes(32),
            authenticatorSelection: AuthenticatorSelectionCriteria::create(
                userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
                residentKey: AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED,
            ),
            attestation: PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            excludeCredentials: array_map(
                fn (PasskeyCredential $credential) => $this->record($credential)->getPublicKeyCredentialDescriptor(),
                $credentials,
            ),
            timeout: 300_000,
        );

        return $this->storeChallenge(PasskeyChallenge::REGISTRATION, $user, $options);
    }

    /** @param array<string, mixed> $credential */
    public function completeRegistration(SubjectUserInterface $user, string $challengeId, array $credential, ?string $label): PasskeyCredential
    {
        $credentialJson = $this->encode($credential);
        $challengeUuid = $this->challengeUuid($challengeId);

        $entity = $this->manager()->wrapInTransaction(function (EntityManagerInterface $manager) use ($user, $challengeUuid, $credentialJson, $label): ?PasskeyCredential {
            $challenge = $manager->find(PasskeyChallenge::class, $challengeUuid, LockMode::PESSIMISTIC_WRITE);
            if (!$challenge instanceof PasskeyChallenge
                || !$challenge->isFor(PasskeyChallenge::REGISTRATION)
                || !$challenge->isUsableAt($this->now(), $this->maxAttempts)
                || $challenge->getUser()?->getSubjectIdentifier() !== $user->getSubjectIdentifier()) {
                return null;
            }
            $manager->lock($user, LockMode::PESSIMISTIC_WRITE);

            try {
                $options = $this->server->deserialize($challenge->optionsJson(), PublicKeyCredentialCreationOptions::class);
                if (!$options instanceof PublicKeyCredentialCreationOptions) {
                    throw new \DomainException('The passkey challenge is invalid.');
                }
                $record = $this->server->register($credentialJson, $options);
            } catch (\Throwable) {
                $challenge->fail();

                return null;
            }

            $entity = new PasskeyCredential(
                $this->credentialHash($record->publicKeyCredentialId),
                $user,
                $this->normalizeLabel($label),
                $this->server->serialize($record),
                $this->now(),
            );
            $challenge->consume($this->now());
            $manager->persist($entity);

            return $entity;
        });

        return $entity ?? throw new \DomainException('The passkey registration response is invalid.');
    }

    /** @return array{challenge: string, publicKey: array<string, mixed>} */
    public function authenticationOptions(): array
    {
        $options = PublicKeyCredentialRequestOptions::create(
            random_bytes(32),
            rpId: $this->server->rpId(),
            userVerification: PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            timeout: 300_000,
        );

        return $this->storeChallenge(PasskeyChallenge::AUTHENTICATION, null, $options);
    }

    /** @param array<string, mixed> $credential */
    public function authenticate(string $challengeId, array $credential): IssuedAccessToken
    {
        $credentialJson = $this->encode($credential);
        $challengeUuid = $this->challengeUuid($challengeId);

        $issued = $this->manager()->wrapInTransaction(function (EntityManagerInterface $manager) use ($challengeUuid, $credentialJson): ?IssuedAccessToken {
            $challenge = $manager->find(PasskeyChallenge::class, $challengeUuid, LockMode::PESSIMISTIC_WRITE);
            if (!$challenge instanceof PasskeyChallenge
                || !$challenge->isFor(PasskeyChallenge::AUTHENTICATION)
                || !$challenge->isUsableAt($this->now(), $this->maxAttempts)) {
                return null;
            }

            try {
                $rawId = $this->server->rawCredentialId($credentialJson);
                $entity = $manager->getRepository(PasskeyCredential::class)->findOneBy([
                    'credentialIdHash' => $this->credentialHash($rawId),
                ]);
                if (!$entity instanceof PasskeyCredential) {
                    throw new \DomainException('Unknown passkey.');
                }
                $manager->lock($entity, LockMode::PESSIMISTIC_WRITE);
                $options = $this->server->deserialize($challenge->optionsJson(), PublicKeyCredentialRequestOptions::class);
                if (!$options instanceof PublicKeyCredentialRequestOptions) {
                    throw new \DomainException('The passkey challenge is invalid.');
                }
                $record = $this->server->authenticate(
                    $credentialJson,
                    $this->record($entity),
                    $options,
                    $this->userHandle($entity->getUser()),
                );
            } catch (\Throwable) {
                $challenge->fail();

                return null;
            }

            $now = $this->now();
            $entity->updateRecord($this->server->serialize($record), $now);
            $challenge->consume($now);

            return $this->tokens->issue($entity->getUser(), 'passkey');
        });

        return $issued ?? throw new \DomainException('The passkey assertion is invalid.');
    }

    /** @param array<string, mixed> $credential */
    public function subjectForCredential(array $credential): ?string
    {
        try {
            $rawId = $this->server->rawCredentialId($this->encode($credential));
            $entity = $this->manager()->getRepository(PasskeyCredential::class)->findOneBy([
                'credentialIdHash' => $this->credentialHash($rawId),
            ]);

            return $entity instanceof PasskeyCredential ? $entity->getUser()->getSubjectIdentifier() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** @return list<array{id: string, label: string, created_at: string, last_used_at: ?string}> */
    public function list(SubjectUserInterface $user): array
    {
        return array_map(static fn (PasskeyCredential $credential): array => [
            'id' => $credential->getId()->toRfc4122(),
            'label' => $credential->getLabel(),
            'created_at' => $credential->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'last_used_at' => $credential->getLastUsedAt()?->format(\DateTimeInterface::ATOM),
        ], $this->credentialsFor($user));
    }

    public function remove(SubjectUserInterface $user, string $id): void
    {
        $uuid = $this->credentialUuid($id);
        $removed = $this->manager()->wrapInTransaction(static function (EntityManagerInterface $manager) use ($user, $uuid): bool {
            $credential = $manager->find(PasskeyCredential::class, $uuid, LockMode::PESSIMISTIC_WRITE);
            if (!$credential instanceof PasskeyCredential
                || $credential->getUser()->getSubjectIdentifier() !== $user->getSubjectIdentifier()) {
                return false;
            }
            $manager->remove($credential);

            return true;
        });

        if (!$removed) {
            throw new \DomainException('The passkey does not exist.');
        }
    }

    /** @return list<PasskeyCredential> */
    private function credentialsFor(SubjectUserInterface $user): array
    {
        /** @var list<PasskeyCredential> $credentials */
        $credentials = $this->manager()->getRepository(PasskeyCredential::class)->findBy(['user' => $user], ['createdAt' => 'ASC']);

        return $credentials;
    }

    /** @return array{challenge: string, publicKey: array<string, mixed>} */
    private function storeChallenge(string $ceremony, ?SubjectUserInterface $user, object $options): array
    {
        $json = $this->server->serialize($options);
        $challenge = new PasskeyChallenge($ceremony, $user, $json, $this->now()->modify('+'.$this->challengeTtl.' seconds'));
        $manager = $this->manager();
        $manager->persist($challenge);
        $manager->flush();

        /** @var array<string, mixed> $publicKey */
        $publicKey = json_decode($json, true, flags: \JSON_THROW_ON_ERROR);

        return ['challenge' => $challenge->getId()->toRfc4122(), 'publicKey' => $publicKey];
    }

    private function record(PasskeyCredential $credential): CredentialRecord
    {
        $record = $this->server->deserialize($credential->recordJson(), CredentialRecord::class);

        return $record instanceof CredentialRecord
            ? $record
            : throw new \RuntimeException('The stored passkey record is invalid.');
    }

    private function credentialHash(string $rawId): string
    {
        return hash('sha256', $rawId);
    }

    private function userHandle(SubjectUserInterface $user): string
    {
        return hash('sha256', "nexum-passkey-user\0".$user->getSubjectIdentifier(), true);
    }

    private function normalizeLabel(?string $label): string
    {
        $label = trim($label ?? 'Passkey');

        return '' !== $label && \strlen($label) <= 100 ? $label : 'Passkey';
    }

    /** @param array<string, mixed> $value */
    private function encode(array $value): string
    {
        return json_encode($value, \JSON_THROW_ON_ERROR);
    }

    private function challengeUuid(string $id): Uuid
    {
        return Uuid::isValid($id)
            ? Uuid::fromString($id)
            : throw new \DomainException('The passkey challenge is invalid or expired.');
    }

    private function credentialUuid(string $id): Uuid
    {
        return Uuid::isValid($id)
            ? Uuid::fromString($id)
            : throw new \DomainException('The passkey does not exist.');
    }

    private function now(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromInterface($this->clock->now());
    }

    private function manager(): EntityManagerInterface
    {
        $manager = $this->registry->getManagerForClass(PasskeyCredential::class);

        return $manager instanceof EntityManagerInterface
            ? $manager
            : throw new \LogicException('No Doctrine ORM manager is configured for Nexum Passkey.');
    }
}
