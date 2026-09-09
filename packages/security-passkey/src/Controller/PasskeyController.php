<?php

declare(strict_types=1);

namespace Nexum\Security\Passkey\Controller;

use Nexum\Api\Error\ApiProblemException;
use Nexum\Api\Serializer\ApiResponseFactoryInterface;
use Nexum\Security\Authorization\SecurityContextInterface;
use Nexum\Security\Contract\SubjectUserInterface;
use Nexum\Security\Passkey\Authentication\PasskeyManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final readonly class PasskeyController
{
    public function __construct(
        private PasskeyManager $passkeys,
        private SecurityContextInterface $security,
        private ApiResponseFactoryInterface $responses,
        private RateLimiterFactory $limiter,
    ) {
    }

    public function registrationOptions(Request $request): JsonResponse
    {
        $user = $this->user();
        $this->enforceRateLimit($request, $user->getSubjectIdentifier());

        return $this->responses->success($this->passkeys->registrationOptions($user), status: 201);
    }

    public function register(Request $request): JsonResponse
    {
        $payload = $this->payload($request);
        $user = $this->user();
        $this->enforceRateLimit($request, $user->getSubjectIdentifier());
        try {
            $credential = $this->passkeys->completeRegistration(
                $user,
                $this->string($payload, 'challenge'),
                $this->object($payload, 'credential'),
                isset($payload['label']) && \is_string($payload['label']) ? $payload['label'] : null,
            );
        } catch (\DomainException) {
            throw $this->invalid();
        }

        return $this->responses->success([
            'id' => $credential->getId()->toRfc4122(),
            'label' => $credential->getLabel(),
        ], status: 201);
    }

    public function authenticationOptions(Request $request): JsonResponse
    {
        $this->enforceRateLimit($request, null);

        return $this->responses->success($this->passkeys->authenticationOptions(), status: 201);
    }

    public function authenticate(Request $request): JsonResponse
    {
        $payload = $this->payload($request);
        $credential = $this->object($payload, 'credential');
        $this->enforceRateLimit($request, $this->passkeys->subjectForCredential($credential));
        try {
            $issued = $this->passkeys->authenticate(
                $this->string($payload, 'challenge'),
                $credential,
            );
        } catch (\DomainException) {
            throw $this->invalid();
        }

        return $this->responses->success([
            'access_token' => $issued->plainToken,
            'token_type' => 'Bearer',
            'expires_at' => $issued->expiresAt->format(\DateTimeInterface::ATOM),
        ], status: 201);
    }

    private function enforceRateLimit(Request $request, ?string $subject): void
    {
        $limits = [$this->limiter->create('ip:'.($request->getClientIp() ?? 'unknown'))->consume()];
        if (null !== $subject) {
            $limits[] = $this->limiter->create('subject:'.$subject)->consume();
        }
        if (array_any($limits, static fn ($limit): bool => !$limit->isAccepted())) {
            throw new ApiProblemException(429, 'rate_limit_exceeded', 'Too many requests', 'Too many passkey attempts. Try again later.');
        }
    }

    public function list(): JsonResponse
    {
        return $this->responses->success($this->passkeys->list($this->user()));
    }

    public function remove(string $id): JsonResponse
    {
        try {
            $this->passkeys->remove($this->user(), $id);
        } catch (\DomainException) {
            throw new ApiProblemException(404, 'passkey_not_found', 'Passkey not found', 'The passkey does not exist.');
        }

        return $this->responses->noContent();
    }

    private function user(): SubjectUserInterface
    {
        $user = $this->security->user();

        return $user instanceof SubjectUserInterface
            ? $user
            : throw new ApiProblemException(401, 'authentication_required', 'Authentication required', 'A valid Bearer access token is required.');
    }

    /** @return array<string, mixed> */
    private function payload(Request $request): array
    {
        try {
            return $request->toArray();
        } catch (\Throwable) {
            throw $this->invalid();
        }
    }

    /** @param array<string, mixed> $payload */
    private function string(array $payload, string $key): string
    {
        $value = $payload[$key] ?? null;
        if (!\is_string($value) || '' === trim($value)) {
            throw $this->invalid();
        }

        return trim($value);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function object(array $payload, string $key): array
    {
        $value = $payload[$key] ?? null;
        if (!\is_array($value)) {
            throw $this->invalid();
        }

        return $value;
    }

    private function invalid(): ApiProblemException
    {
        return new ApiProblemException(401, 'invalid_passkey_challenge', 'Passkey verification failed', 'The challenge or passkey response is invalid.');
    }
}
