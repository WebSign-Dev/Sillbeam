<?php

declare(strict_types=1);

namespace Nexum\Security\Authorization;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/** @extends Voter<string, mixed> */
final class PermissionVoter extends Voter
{
    public function __construct(private readonly PermissionResolver $permissions)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return 1 === preg_match('/^[a-z][a-z0-9_-]*(?:\.[a-z][a-z0-9_-]*)+$/', $attribute);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        return $user instanceof UserInterface && $this->permissions->has($user, $attribute);
    }
}
