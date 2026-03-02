<?php

declare(strict_types=1);

namespace Modules\Department\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Department\Models\Department;
use Modules\User\Models\User;

/**
 * Class DepartmentPolicy
 * 
 * Controls access to Department resources.
 */
class DepartmentPolicy
{
    use HandlesAuthorization;

    /**
     * Internal: Check if the user has the master management permission.
     */
    protected function canManage(User $user): bool
    {
        // 1. SuperAdmin bypass
        if ($user->hasRole(\Modules\Permission\Enums\Role::SUPER_ADMIN->value)) {
            return true;
        }

        // 2. Standard permission check
        return $user->hasPermissionTo('department.manage');
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->canManage($user) || $user->hasPermissionTo('department.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Department $department): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Department $department): bool
    {
        return $this->canManage($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Department $department): bool
    {
        return $this->canManage($user);
    }
}
