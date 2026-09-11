<?php

declare(strict_types=1);

namespace Nexum\Security\Authorization;

use Symfony\Component\Security\Core\User\UserInterface;

interface SecurityContextInterface
{
    public function user(): ?UserInterface;

    public function identifier(): ?string;

    /** @return list<string> */
    public function permissions(): array;

    public function isGranted(string $permission, mixed $subject = null): bool;
}
