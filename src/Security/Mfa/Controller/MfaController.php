<?php

declare(strict_types=1);

namespace Nexum\Security\Mfa\Controller;

use Nexum\Api\Error\ApiProblemException;
use Nexum\Api\Serializer\ApiResponseFactoryInterface;
use Nexum\Security\Authorization\SecurityContextInterface;
use Nexum\Security\Contract\SubjectUserInterface;
use Nexum\Security\Mfa\Authentication\MfaManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final readonly class MfaController
{
    public function __construct(
        private MfaManager $mfa,
        private SecurityContextInterface $security,
        private ApiResponseFactoryInterface $responses,
        private RateLimiterFactory $limiter,
    ) {
    }

    public function status(): JsonResponse
    {
        return $this->responses->success(['enabled' => $this->mfa->isEnabled($this->user())]);
    }

    public function beginEnrollment(Request $request): JsonResponse
    {
        $user = $this->user();
        $this->enforceRateLimit($request, $user->getSubjectIdentifier());
        try {
            $enrollment = $this->mfa->beginEnrollment($user);
        } catch (\DomainException $exception) {
            throw new ApiProblemException(409, 'mfa_already_enabled', 'MFA already enabled', $exception->getMessage());
        }

        return $this->responses->success($enrollment, status: 201);
    }

    public function confirmEnrollment(Request $request): JsonResponse
    {
        $user = $this->user();
        $this->enforceRateLimit($request, $user->getSubjectIdentifier());
        try {
            $codes = $this->mfa->confirmEnrollment($user, $this->requiredString($request, 'code'));
        } catch (\DomainException $exception) {
            throw new ApiProblemException(400, 'invalid_mfa_code', 'Invalid MFA code', $exception->getMessage());
        }

        return $this->responses->success([
            'enabled' => true,
            'recovery_codes' => $codes,
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $challenge = $this->requiredString($request, 'challenge');
        $code = $this->requiredString($request, 'code');
        $this->enforceRateLimit($request, $this->mfa->subjectForChallenge($challenge));
        try {
            $issued = $this->mfa->verifyChallenge($challenge, $code);
        } catch (\DomainException) {
            throw new ApiProblemException(401, 'invalid_mfa_challenge', 'MFA verification failed', 'The challenge or verification code is invalid.');
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
            throw new ApiProblemException(429, 'rate_limit_exceeded', 'Too many requests', 'Too many verification attempts. Try again later.');
        }
    }

    public function disable(Request $request): JsonResponse
    {
        $user = $this->user();
        $this->enforceRateLimit($request, $user->getSubjectIdentifier());
        try {
            $this->mfa->disable($user, $this->requiredString($request, 'code'));
        } catch (\DomainException $exception) {
            throw new ApiProblemException(400, 'invalid_mfa_code', 'Unable to disable MFA', $exception->getMessage());
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

    private function requiredString(Request $request, string $key): string
    {
        try {
            $value = $request->toArray()[$key] ?? null;
        } catch (\Throwable) {
            $value = null;
        }

        if (!\is_string($value) || '' === trim($value)) {
            throw new ApiProblemException(400, 'invalid_request', 'Invalid request', \sprintf('The field "%s" is required.', $key));
        }

        return trim($value);
    }
}
