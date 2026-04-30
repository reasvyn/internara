<?php

declare(strict_types=1);

namespace Modules\Permission\Services\Contracts;

use Modules\Permission\Models\Permission;
use Modules\Permission\Models\Role;

interface PermissionManager
{
    public function createPermission(
        string $name,
        string $description,
        string $module,
        string $guardName = 'web',
    ): Permission;

    public function createRole(
        string $name,
        string $description,
        string $module,
        string $guardName = 'web',
    ): Role;

    public function givePermissionToRole(
        string $roleName,
        array $permissions,
        string $guardName = 'web',
    ): ?Role;
}
