<?php

declare(strict_types=1);

namespace Modules\Internship\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\User\Models\User;

class InternshipPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['internship.view', 'internship.manage']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, \Modules\Internship\Models\Internship $internship): bool
    {
        return $user->hasAnyPermission(['internship.view', 'internship.manage']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('internship.manage');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, \Modules\Internship\Models\Internship $internship): bool
    {
        return $user->hasAnyPermission(['internship.update', 'internship.manage']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, \Modules\Internship\Models\Internship $internship): bool
    {
        return $user->hasPermissionTo('internship.manage');
    }
}
