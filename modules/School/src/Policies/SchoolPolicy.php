<?php

declare(strict_types=1);

namespace Modules\School\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\School\Models\School;
use Modules\User\Models\User;

class SchoolPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('school.view') || $user->hasPermissionTo('school.manage');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, School $school): bool
    {
        return $user->hasPermissionTo('school.view') || $user->hasPermissionTo('school.manage');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Biasanya school hanya satu (single record), jadi create dibatasi
        return $user->hasPermissionTo('school.manage');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, School $school): bool
    {
        return $user->hasPermissionTo('school.update') || $user->hasPermissionTo('school.manage');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, School $school): bool
    {
        return $user->hasPermissionTo('school.manage');
    }
}
