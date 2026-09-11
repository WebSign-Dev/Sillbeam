<?php

declare(strict_types=1);

namespace Nexum\Security\Http;

use Nexum\Api\Serializer\ApiResponseFactoryInterface;
use Nexum\Security\Authentication\AccessTokenManager;
use Nexum\Security\Authentication\SecondFactorGateInterface;
use Nexum\Security\Contract\SubjectUserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

final readonly class TokenAuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private AccessTokenManager $tokens,
        private SecondFactorGateInterface $secondFactor,
        private ApiResponseFactoryInterface $responses,
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $user = $token->getUser();

        if (!$user instanceof SubjectUserInterface) {
            throw new \LogicException('A Nexum access token can only be issued for a stable Nexum subject.');
        }

        $challenge = $this->secondFactor->begin($user);

        if (null !== $challenge) {
            return $this->responses->success([
                'mfa_required' => true,
                'challenge' => $challenge->id,
                'methods' => $challenge->methods,
                'expires_at' => $challenge->expiresAt->format(\DateTimeInterface::ATOM),
            ], status: 202);
        }

        $label = null;

        try {
            $payload = $request->toArray();
            $submittedLabel = $payload['label'] ?? null;

            if (\is_string($submittedLabel) && '' !== trim($submittedLabel)) {
                $trimmedLabel = trim($submittedLabel);
                $label = \strlen($trimmedLabel) <= 100 ? $trimmedLabel : null;
            }
        } catch (\Throwable) {
        }

        $issued = $this->tokens->issue($user, $label);

        return $this->responses->success([
            'access_token' => $issued->plainToken,
            'token_type' => 'Bearer',
            'expires_at' => $issued->expiresAt->format(\DateTimeInterface::ATOM),
        ], status: 201);
    }
}
