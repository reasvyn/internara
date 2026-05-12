<?php

declare(strict_types=1);

namespace App\Actions\User;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UpdateRolePermissionsAction
{
    public function execute(Role $role, array $permissionNames): void
    {
        $permissions = Permission::whereIn('name', $permissionNames)->get();

        $role->syncPermissions($permissions);
    }
}
