<?php

declare(strict_types=1);

namespace Modules\School\Policies;

use Modules\Permission\Enums\Permission;
use Modules\Permission\Enums\Role;
use Modules\School\Models\School;
use Modules\User\Models\User;

/**
 * Class SchoolPolicy
 *
 * Controls access to institutional metadata.
 */
class SchoolPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, ?School $school = null): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if (session('setup_authorized')) {
            return true;
        }

        return $user->hasPermissionTo(Permission::SCHOOL_MANAGE->value);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ?School $school = null): bool
    {
        if (session('setup_authorized')) {
            return true;
        }

        return $user->hasPermissionTo(Permission::SCHOOL_MANAGE->value);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * Deletion of institutional record is restricted to Super Admin only.
     */
    public function delete(User $user, ?School $school = null): bool
    {
        return $user->hasRole(Role::SUPER_ADMIN->value);
    }

    /**
     * Determine whether the user can force delete the model.
     */
    public function forceDelete(User $user, ?School $school = null): bool
    {
        return $user->hasRole(Role::SUPER_ADMIN->value);
    }
}
