<?php

declare(strict_types=1);

namespace Modules\Permission\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Permission\Models\Permission;
use Modules\Permission\Models\Role;
use Modules\Permission\Services\Contracts\AccessManagementService as Contract;
use Modules\Shared\Services\BaseService;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Exceptions\RoleDoesNotExist;

/**
 * Orchestrates access management with business logic and audit trail.
 *
 * This service centralizes all CRUD operations for roles and permissions,
 * providing a single point of control for the access management domain.
 */
class AccessManagementService extends BaseService implements Contract
{
    /**
     * Create or update a permission.
     */
    public function createPermission(
        string $name,
        string $description,
        string $module,
        string $guardName = 'web',
    ): Permission {
        $permission = Permission::updateOrCreate(
            ['name' => $name, 'guard_name' => $guardName],
            ['description' => $description, 'module' => $module],
        );

        $this->refreshCache();

        return $permission;
    }

    /**
     * Create or update a role.
     */
    public function createRole(
        string $name,
        string $description,
        string $module,
        string $guardName = 'web',
    ): Role {
        $role = Role::updateOrCreate(
            ['name' => $name, 'guard_name' => $guardName],
            ['description' => $description, 'module' => $module],
        );

        $this->refreshCache();

        return $role;
    }

    /**
     * Assign permissions to a role with audit trail.
     */
    public function assignPermissionsToRole(
        string $roleName,
        array $permissions,
        string $guardName = 'web',
    ): Role {
        $role = $this->findRoleOrFail($roleName, $guardName);

        $role->syncPermissions($permissions);

        $this->refreshCache();

        return $role;
    }

    /**
     * Remove a role and revoke all its permissions.
     */
    public function deleteRole(string $roleName, string $guardName = 'web'): bool
    {
        $role = $this->findRoleOrFail($roleName, $guardName);

        $role->revokeAllPermissions();
        $role->delete();

        $this->refreshCache();

        return true;
    }

    /**
     * Remove a permission.
     */
    public function deletePermission(string $permissionName, string $guardName = 'web'): bool
    {
        $permission = $this->findPermissionOrFail($permissionName, $guardName);

        $permission->delete();

        $this->refreshCache();

        return true;
    }

    /**
     * Refresh the permission cache.
     */
    public function refreshCache(): void
    {
        try {
            Cache::forget(config('permission.cache.key', 'spatie.permission.cache'));
        } catch (\Exception $e) {
            // Log the error but don't crash during seeding
            report($e);
        }
    }

    /**
     * Find a role or throw an exception.
     *
     * @throws RoleDoesNotExist
     */
    protected function findRoleOrFail(string $name, string $guard): Role
    {
        return Role::findByName($name, $guard);
    }

    /**
     * Find a permission or throw an exception.
     *
     * @throws PermissionDoesNotExist
     */
    protected function findPermissionOrFail(string $name, string $guard): Permission
    {
        return Permission::findByName($name, $guard);
    }
}
