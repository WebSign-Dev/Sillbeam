<?php

declare(strict_types=1);

namespace Nexum\Security\Authorization;

final readonly class RolePermissionMap
{
    /** @param array<string, list<string>> $permissionsByRole */
    public function __construct(private array $permissionsByRole)
    {
    }

    /**
     * @param array<string> $roles
     *
     * @return list<string>
     */
    public function permissionsFor(array $roles): array
    {
        $permissions = [];

        foreach ($roles as $role) {
            foreach ($this->permissionsByRole[$role] ?? [] as $permission) {
                $permissions[$permission] = true;
            }
        }

        $resolved = array_keys($permissions);
        sort($resolved);

        return $resolved;
    }
}
