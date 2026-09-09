<?php

declare(strict_types=1);

namespace Nexum\User\Security;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class PasswordManager implements PasswordManagerInterface
{
    public function __construct(private UserPasswordHasherInterface $hasher)
    {
    }

    public function hash(UserInterface&PasswordAuthenticatedUserInterface $user, #[\SensitiveParameter] string $plainPassword): string
    {
        return $this->hasher->hashPassword($user, $plainPassword);
    }

    public function needsRehash(UserInterface&PasswordAuthenticatedUserInterface $user): bool
    {
        return $this->hasher->needsRehash($user);
    }
}
