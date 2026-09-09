<?php

declare(strict_types=1);

namespace Nexum\Security\Authorization;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class SecurityContext implements SecurityContextInterface
{
    public function __construct(
        private TokenStorageInterface $tokens,
        private AuthorizationCheckerInterface $authorization,
        private PermissionResolver $permissions,
    ) {
    }

    public function user(): ?UserInterface
    {
        $user = $this->tokens->getToken()?->getUser();

        return $user instanceof UserInterface ? $user : null;
    }

    public function identifier(): ?string
    {
        return $this->user()?->getUserIdentifier();
    }

    public function permissions(): array
    {
        $user = $this->user();

        return null === $user ? [] : $this->permissions->permissionsFor($user);
    }

    public function isGranted(string $permission, mixed $subject = null): bool
    {
        return $this->authorization->isGranted($permission, $subject);
    }
}
