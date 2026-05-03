<?php

declare(strict_types=1);

namespace Modules\Department\Policies;

use Modules\Department\Models\Department;
use Modules\Permission\Enums\Permission;
use Modules\Permission\Enums\Role;
use Modules\User\Models\User;

/**
 * Class DepartmentPolicy
 *
 * Policy for Department model operations.
 */
class DepartmentPolicy
{
    /**
     * Determine whether the user can view any departments.
     */
    public function viewAny(?User $user): bool
    {
        if (session('setup_authorized')) {
            return true;
        }

        return $user?->hasPermissionTo(Permission::DEPARTMENT_VIEW->value) ?? false;
    }

    /**
     * Determine whether the user can view the department.
     */
    public function view(User $user, Department $department): bool
    {
        if (session('setup_authorized')) {
            return true;
        }

        return $user->hasPermissionTo(Permission::DEPARTMENT_VIEW->value);
    }

    /**
     * Determine whether the user can create departments.
     */
    public function create(User $user): bool
    {
        if (session('setup_authorized')) {
            return true;
        }

        return $user->hasPermissionTo(Permission::DEPARTMENT_CREATE->value);
    }

    /**
     * Determine whether the user can update the department.
     */
    public function update(User $user, Department $department): bool
    {
        if (session('setup_authorized')) {
            return true;
        }

        return $user->hasPermissionTo(Permission::DEPARTMENT_UPDATE->value);
    }

    /**
     * Determine whether the user can delete the department.
     */
    public function delete(User $user, Department $department): bool
    {
        if (session('setup_authorized')) {
            return true;
        }

        if (! $user->hasPermissionTo(Permission::DEPARTMENT_DELETE->value)) {
            return false;
        }

        return ! $department->profiles()->exists();
    }

    /**
     * Determine whether the user can force delete the department.
     */
    public function forceDelete(User $user, Department $department): bool
    {
        return $user->hasRole(Role::SUPER_ADMIN->value);
    }
}
