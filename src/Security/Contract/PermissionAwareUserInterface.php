<?php

declare(strict_types=1);

namespace Nexum\Security\Contract;

interface PermissionAwareUserInterface
{
    /** @return list<string> */
    public function getPermissions(): array;
}
