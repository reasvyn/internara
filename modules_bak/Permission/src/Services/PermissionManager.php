<?php

declare(strict_types=1);

namespace Modules\Permission\Services;

use Modules\Permission\Models\Permission;
use Modules\Permission\Models\Role;
use Modules\Permission\Services\Contracts\PermissionManager as PermissionManagerContract;
use Modules\Shared\Services\BaseService;

class PermissionManager extends BaseService implements PermissionManagerContract
{
    public function createPermission(
        string $name,
        string $description,
        string $module,
        string $guardName = 'web',
    ): Permission {
        return Permission::updateOrCreate(
            ['name' => $name, 'guard_name' => $guardName],
            ['description' => $description, 'module' => $module],
        );
    }

    public function createRole(
        string $name,
        string $description,
        string $module,
        string $guardName = 'web',
    ): Role {
        return Role::updateOrCreate(
            ['name' => $name, 'guard_name' => $guardName],
            ['description' => $description, 'module' => $module],
        );
    }

    public function givePermissionToRole(
        string $roleName,
        array $permissions,
        string $guardName = 'web',
    ): ?Role {
        $role = Role::findByName($roleName, $guardName);
        if ($role) {
            $role->givePermissionTo($permissions);
        }

        return $role;
    }
}
