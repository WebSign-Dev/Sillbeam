<?php

declare(strict_types=1);

namespace Nexum\Security\Controller;

use Nexum\Api\Error\ApiProblemException;
use Nexum\Api\Serializer\ApiResponseFactoryInterface;
use Nexum\Core\Contract\SubjectInterface;
use Nexum\Security\Authentication\AccessTokenManager;
use Nexum\Security\Authorization\SecurityContextInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final readonly class TokenController
{
    public function __construct(
        private AccessTokenManager $tokens,
        private SecurityContextInterface $security,
        private ApiResponseFactoryInterface $responses,
    ) {
    }

    public function issue(): never
    {
        throw new \LogicException('The token issue route must be intercepted by Symfony json_login.');
    }

    public function revoke(Request $request): JsonResponse
    {
        $token = $this->bearerToken($request);

        if (!$this->tokens->revoke($token)) {
            throw new ApiProblemException(401, 'invalid_token', 'Invalid token', 'The access token is invalid or already revoked.');
        }

        return $this->responses->noContent();
    }

    public function current(): JsonResponse
    {
        $user = $this->security->user();

        if (null === $user) {
            throw new ApiProblemException(401, 'authentication_required', 'Authentication required', 'A valid Bearer access token is required.');
        }

        return $this->responses->success([
            'identifier' => $user->getUserIdentifier(),
            'subject' => $user instanceof SubjectInterface ? $user->getSubjectIdentifier() : null,
            'roles' => $user->getRoles(),
            'permissions' => $this->security->permissions(),
        ]);
    }

    private function bearerToken(Request $request): string
    {
        $authorization = $request->headers->get('Authorization');

        if (null === $authorization || 1 !== preg_match('/^Bearer\s+(\S+)$/i', $authorization, $matches)) {
            throw new ApiProblemException(401, 'invalid_token', 'Invalid token', 'A Bearer access token is required.');
        }

        return $matches[1];
    }
}
