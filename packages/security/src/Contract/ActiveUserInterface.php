<?php

declare(strict_types=1);

namespace Nexum\Security\Contract;

interface ActiveUserInterface
{
    public function isActive(): bool;
}
