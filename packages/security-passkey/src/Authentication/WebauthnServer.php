<?php

declare(strict_types=1);

namespace Nexum\Security\Passkey\Authentication;

use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\CredentialRecord;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;

final readonly class WebauthnServer
{
    private SerializerInterface $serializer;
    private AuthenticatorAttestationResponseValidator $attestationValidator;
    private AuthenticatorAssertionResponseValidator $assertionValidator;

    /** @param list<string> $allowedOrigins */
    public function __construct(private string $rpId, array $allowedOrigins)
    {
        $attestationSupport = new AttestationStatementSupportManager([new NoneAttestationStatementSupport()]);
        $factory = new CeremonyStepManagerFactory();
        $factory->setAttestationStatementSupportManager($attestationSupport);
        $factory->setAllowedOrigins($allowedOrigins);

        $this->serializer = (new WebauthnSerializerFactory($attestationSupport))->create();
        $this->attestationValidator = AuthenticatorAttestationResponseValidator::create($factory->creationCeremony());
        $this->assertionValidator = AuthenticatorAssertionResponseValidator::create($factory->requestCeremony());
    }

    public function serialize(object $value): string
    {
        return $this->serializer->serialize($value, 'json');
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    public function deserialize(string $json, string $class): object
    {
        return $this->serializer->deserialize($json, $class, 'json');
    }

    public function register(string $credentialJson, PublicKeyCredentialCreationOptions $options): CredentialRecord
    {
        $credential = $this->deserialize($credentialJson, PublicKeyCredential::class);
        if (!$credential instanceof PublicKeyCredential || !$credential->response instanceof AuthenticatorAttestationResponse) {
            throw new \DomainException('The passkey registration response is invalid.');
        }

        return $this->attestationValidator->check($credential->response, $options, $this->rpId);
    }

    public function authenticate(
        string $credentialJson,
        CredentialRecord $record,
        PublicKeyCredentialRequestOptions $options,
        string $userHandle,
    ): CredentialRecord {
        $credential = $this->deserialize($credentialJson, PublicKeyCredential::class);
        if (!$credential instanceof PublicKeyCredential || !$credential->response instanceof AuthenticatorAssertionResponse) {
            throw new \DomainException('The passkey authentication response is invalid.');
        }

        return $this->assertionValidator->check($record, $credential->response, $options, $this->rpId, $userHandle);
    }

    public function rawCredentialId(string $credentialJson): string
    {
        $credential = $this->deserialize($credentialJson, PublicKeyCredential::class);
        if (!$credential instanceof PublicKeyCredential) {
            throw new \DomainException('The passkey response is invalid.');
        }

        return $credential->rawId;
    }
}
