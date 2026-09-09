<?php

declare(strict_types=1);

namespace Nexum\Security\Authorization;

use Nexum\Security\Contract\PermissionAwareUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class PermissionResolver
{
    public function __construct(private RolePermissionMap $rolePermissionMap)
    {
    }

    /** @return list<string> */
    public function permissionsFor(UserInterface $user): array
    {
        $permissions = array_fill_keys($this->rolePermissionMap->permissionsFor($user->getRoles()), true);

        if ($user instanceof PermissionAwareUserInterface) {
            foreach ($user->getPermissions() as $permission) {
                $permissions[$permission] = true;
            }
        }

        $resolved = array_keys($permissions);
        sort($resolved);

        return $resolved;
    }

    public function has(UserInterface $user, string $permission): bool
    {
        $permissions = $this->permissionsFor($user);

        return \in_array('*', $permissions, true) || \in_array($permission, $permissions, true);
    }
}
