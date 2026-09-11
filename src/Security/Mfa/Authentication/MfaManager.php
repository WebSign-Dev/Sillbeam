<?php

declare(strict_types=1);

namespace Nexum\Security\Mfa\Authentication;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Nexum\Security\Authentication\AccessTokenManager;
use Nexum\Security\Authentication\IssuedAccessToken;
use Nexum\Security\Contract\SubjectUserInterface;
use Nexum\Security\Mfa\Crypto\SecretCipher;
use Nexum\Security\Mfa\Entity\MfaChallenge;
use Nexum\Security\Mfa\Entity\TotpEnrollment;
use OTPHP\TOTP;
use Psr\Clock\ClockInterface;
use Symfony\Component\Uid\Uuid;

final readonly class MfaManager
{
    /** @var non-empty-string */
    private string $issuer;

    public function __construct(
        private ManagerRegistry $registry,
        private SecretCipher $cipher,
        private ClockInterface $clock,
        private AccessTokenManager $tokens,
        string $issuer,
        private int $maxAttempts,
    ) {
        if ('' === $issuer) {
            throw new \InvalidArgumentException('The MFA issuer must not be empty.');
        }
        $this->issuer = $issuer;
    }

    /** @return array{secret: string, provisioning_uri: string} */
    public function beginEnrollment(SubjectUserInterface $user): array
    {
        $totp = TOTP::generate($this->clock)->withIssuer($this->issuer)->withLabel($user->getUserIdentifier());
        $encryptedSecret = $this->cipher->encrypt($totp->getSecret());

        $this->manager()->wrapInTransaction(static function (EntityManagerInterface $manager) use ($user, $encryptedSecret): void {
            $manager->lock($user, LockMode::PESSIMISTIC_WRITE);
            $enrollment = $manager->getRepository(TotpEnrollment::class)->findOneBy(['user' => $user]);
            if ($enrollment instanceof TotpEnrollment && $enrollment->isConfirmed()) {
                throw new \DomainException('MFA is already enabled.');
            }

            if ($enrollment instanceof TotpEnrollment) {
                $manager->lock($enrollment, LockMode::PESSIMISTIC_WRITE);
                $enrollment->replacePendingSecret($encryptedSecret);
            } else {
                $manager->persist(new TotpEnrollment($user, $encryptedSecret));
            }
        });

        return ['secret' => $totp->getSecret(), 'provisioning_uri' => $totp->getProvisioningUri()];
    }

    /** @return list<string> */
    public function confirmEnrollment(SubjectUserInterface $user, #[\SensitiveParameter] string $code): array
    {
        return $this->manager()->wrapInTransaction(function (EntityManagerInterface $manager) use ($user, $code): array {
            $manager->lock($user, LockMode::PESSIMISTIC_WRITE);
            $enrollment = $manager->getRepository(TotpEnrollment::class)->findOneBy(['user' => $user]);
            if (!$enrollment instanceof TotpEnrollment || $enrollment->isConfirmed()) {
                throw new \DomainException('No pending MFA enrollment exists.');
            }
            $manager->lock($enrollment, LockMode::PESSIMISTIC_WRITE);

            if (!$this->totp($enrollment)->verify($this->normalizeTotp($code))) {
                throw new \DomainException('The verification code is invalid.');
            }

            $plainCodes = [];
            $hashes = [];
            for ($i = 0; $i < 10; ++$i) {
                $plain = strtoupper(bin2hex(random_bytes(5)));
                $plainCodes[] = substr($plain, 0, 5).'-'.substr($plain, 5);
                $hashes[] = $this->cipher->keyedHash($plain);
            }

            $enrollment->confirm($this->now(), $hashes);
            $this->tokens->revokeAllForUser($user);

            return $plainCodes;
        });
    }

    public function verifyChallenge(string $challengeId, #[\SensitiveParameter] string $code): IssuedAccessToken
    {
        if (!Uuid::isValid($challengeId)) {
            throw new \DomainException('The MFA challenge is invalid or expired.');
        }

        $issued = $this->manager()->wrapInTransaction(function (EntityManagerInterface $manager) use ($challengeId, $code): ?IssuedAccessToken {
            $now = $this->now();
            $challenge = $manager->find(MfaChallenge::class, Uuid::fromString($challengeId), LockMode::PESSIMISTIC_WRITE);
            if (!$challenge instanceof MfaChallenge || !$challenge->isUsableAt($now, $this->maxAttempts)) {
                return null;
            }

            $enrollment = $manager->getRepository(TotpEnrollment::class)->findOneBy(['user' => $challenge->getUser()]);
            if (!$enrollment instanceof TotpEnrollment || !$enrollment->isConfirmed()) {
                return null;
            }
            $manager->lock($enrollment, LockMode::PESSIMISTIC_WRITE);

            if (!$this->verifyCode($enrollment, $code, $now)) {
                $challenge->fail();

                return null;
            }

            $challenge->consume($now);

            return $this->tokens->issue($challenge->getUser(), 'mfa');
        });

        return $issued ?? throw new \DomainException('The verification code is invalid.');
    }

    public function subjectForChallenge(string $challengeId): ?string
    {
        if (!Uuid::isValid($challengeId)) {
            return null;
        }
        $challenge = $this->manager()->find(MfaChallenge::class, Uuid::fromString($challengeId));

        return $challenge instanceof MfaChallenge ? $challenge->getUser()->getSubjectIdentifier() : null;
    }

    public function isEnabled(SubjectUserInterface $user): bool
    {
        $enrollment = $this->manager()->getRepository(TotpEnrollment::class)->findOneBy(['user' => $user]);

        return $enrollment instanceof TotpEnrollment && $enrollment->isConfirmed();
    }

    public function disable(SubjectUserInterface $user, #[\SensitiveParameter] string $code): void
    {
        $verified = $this->manager()->wrapInTransaction(function (EntityManagerInterface $manager) use ($user, $code): bool {
            $manager->lock($user, LockMode::PESSIMISTIC_WRITE);
            $enrollment = $manager->getRepository(TotpEnrollment::class)->findOneBy(['user' => $user]);
            if (!$enrollment instanceof TotpEnrollment || !$enrollment->isConfirmed()) {
                throw new \DomainException('MFA is not enabled.');
            }
            $manager->lock($enrollment, LockMode::PESSIMISTIC_WRITE);

            if (!$this->verifyCode($enrollment, $code, $this->now())) {
                return false;
            }

            $manager->remove($enrollment);
            $this->tokens->revokeAllForUser($user);

            return true;
        });

        if (!$verified) {
            throw new \DomainException('The verification code is invalid.');
        }
    }

    private function verifyCode(TotpEnrollment $enrollment, string $code, \DateTimeImmutable $now): bool
    {
        $normalized = strtoupper(str_replace(['-', ' '], '', trim($code)));
        if (1 === preg_match('/^\d{6}$/', $normalized)) {
            $timeStep = intdiv($now->getTimestamp(), 30);
            $timestamp = $now->getTimestamp();
            if ($timestamp < 0 || $enrollment->lastUsedTimeStep() === $timeStep || !$this->totp($enrollment)->verify($normalized, $timestamp)) {
                return false;
            }
            $enrollment->markTimeStepUsed($timeStep);

            return true;
        }

        return 1 === preg_match('/^[A-F0-9]{10}$/', $normalized)
            && $enrollment->consumeRecoveryCodeHash($this->cipher->keyedHash($normalized));
    }

    private function totp(TotpEnrollment $enrollment): TOTP
    {
        $secret = $this->cipher->decrypt($enrollment->encryptedSecret());
        if ('' === $secret) {
            throw new \RuntimeException('The stored MFA secret is empty.');
        }

        return TOTP::createFromSecret($secret, $this->clock);
    }

    /** @return non-empty-string */
    private function normalizeTotp(string $code): string
    {
        $normalized = trim($code);
        if (1 !== preg_match('/^\d{6}$/', $normalized)) {
            throw new \DomainException('The verification code is invalid.');
        }

        return $normalized;
    }

    private function now(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromInterface($this->clock->now());
    }

    private function manager(): EntityManagerInterface
    {
        $manager = $this->registry->getManagerForClass(TotpEnrollment::class);

        return $manager instanceof EntityManagerInterface
            ? $manager
            : throw new \LogicException('No Doctrine ORM manager is configured for Nexum MFA.');
    }
}
