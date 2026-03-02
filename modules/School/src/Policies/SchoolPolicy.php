<?php

declare(strict_types=1);

namespace Modules\School\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\School\Models\School;
use Modules\User\Models\User;

/**
 * Class SchoolPolicy
 * 
 * Controls access to institutional metadata.
 */
class SchoolPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        // Publicly visible or requires basic access
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, School $school): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('school.manage');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, School $school): bool
    {
        return $user->hasPermissionTo('school.manage');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, School $school): bool
    {
        // Institutional records are rarely deleted, but managed by authorized personnel.
        return $user->hasPermissionTo('school.manage');
    }
}
