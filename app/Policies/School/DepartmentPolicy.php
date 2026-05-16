<?php

declare(strict_types=1);

namespace App\Policies\School;

use App\Models\Department;
use App\Models\User;
use App\Policies\Shared\BasePolicy;

/**
 * S1 - Secure: Delete blocked if department has student profiles.
 * S2 - Sustain: Clear authorization rules for academic units.
 */
class DepartmentPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any departments.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the department.
     */
    public function view(?User $user, Department $department): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create departments.
     */
    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determine whether the user can update the department.
     */
    public function update(User $user, Department $department): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determine whether the user can delete the department.
     * Cannot delete if student profiles are associated.
     * Business rule is enforced by DepartmentState::canBeDeleted().
     */
    public function delete(User $user, Department $department): bool
    {
        return $this->isAdmin($user) && $department->asDepartmentState()->canBeDeleted();
    }

    /**
     * Determine whether the user can force delete the department.
     */
    public function forceDelete(User $user, Department $department): bool
    {
        return $user->hasRole('super_admin');
    }
}
