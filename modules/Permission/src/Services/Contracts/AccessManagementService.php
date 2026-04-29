<?php

declare(strict_types=1);

namespace Modules\Permission\Services\Contracts;

use Modules\Permission\Models\Permission;
use Modules\Permission\Models\Role;

interface AccessManagementService
{
    /**
     * Create or update a permission.
     */
    public function createPermission(
        string $name,
        string $description,
        string $module,
        string $guardName = 'web',
    ): Permission;

    /**
     * Create or update a role.
     */
    public function createRole(
        string $name,
        string $description,
        string $module,
        string $guardName = 'web',
    ): Role;

    /**
     * Assign permissions to a role with audit trail.
     */
    public function assignPermissionsToRole(
        string $roleName,
        array $permissions,
        string $guardName = 'web',
    ): Role;

    /**
     * Remove a role and revoke all its permissions.
     */
    public function deleteRole(string $roleName, string $guardName = 'web'): bool;

    /**
     * Remove a permission.
     */
    public function deletePermission(string $permissionName, string $guardName = 'web'): bool;

    /**
     * Refresh the permission cache.
     */
    public function refreshCache(): void;
}
