<?php

declare(strict_types=1);

namespace Modules\Assignment\Policies;

use Illuminate\Database\Eloquent\Model;
use Modules\Assignment\Models\Assignment;
use Modules\Assignment\Models\AssignmentType;
use Modules\Permission\Enums\Permission;
use Modules\Permission\Enums\Role;
use Modules\User\Models\User;

/**
 * Class AssignmentPolicy
 *
 * Controls access to Assignment and AssignmentType model operations.
 */
class AssignmentPolicy
{
    /**
     * Determine whether the user can view any assignments.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            Permission::ASSIGNMENT_VIEW->value,
            Permission::ASSIGNMENT_MANAGE->value,
        ]);
    }

    /**
     * Determine whether the user can view the assignment.
     */
    public function view(User $user, Assignment $assignment): bool
    {
        return $user->hasAnyPermission([
            Permission::ASSIGNMENT_VIEW->value,
            Permission::ASSIGNMENT_MANAGE->value,
        ]);
    }

    /**
     * Determine whether the user can view assignment types.
     */
    public function viewType(User $user, AssignmentType $type): bool
    {
        return $user->hasAnyPermission([
            Permission::ASSIGNMENT_VIEW->value,
            Permission::ASSIGNMENT_MANAGE->value,
        ]);
    }

    /**
     * Determine whether the user can create assignments.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo(Permission::ASSIGNMENT_MANAGE->value);
    }

    /**
     * Determine whether the user can update assignments.
     */
    public function update(User $user, Assignment $assignment): bool
    {
        return $user->hasPermissionTo(Permission::ASSIGNMENT_MANAGE->value);
    }

    /**
     * Determine whether the user can delete assignments.
     */
    public function delete(User $user, Assignment $assignment): bool
    {
        return $user->hasPermissionTo(Permission::ASSIGNMENT_MANAGE->value);
    }

    /**
     * Determine whether the user can grade assignments.
     */
    public function grade(User $user, Assignment $assignment): bool
    {
        return $user->hasPermissionTo(Permission::ASSIGNMENT_GRADE->value);
    }

    /**
     * Determine whether the user can force delete.
     */
    public function forceDelete(User $user, Assignment $assignment): bool
    {
        return $user->hasRole(Role::SUPER_ADMIN->value);
    }
}
