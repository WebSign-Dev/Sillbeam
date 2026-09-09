<?php

declare(strict_types=1);

namespace Nexum\User\Security;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

interface PasswordManagerInterface
{
    public function hash(UserInterface&PasswordAuthenticatedUserInterface $user, #[\SensitiveParameter] string $plainPassword): string;

    public function needsRehash(UserInterface&PasswordAuthenticatedUserInterface $user): bool;
}
