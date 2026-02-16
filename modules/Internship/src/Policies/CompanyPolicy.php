<?php

declare(strict_types=1);

namespace Modules\Internship\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\User\Models\User;

class CompanyPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any companies.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('internship.view');
    }

    /**
     * Determine whether the user can view the company.
     */
    public function view(User $user): bool
    {
        return $user->can('internship.view');
    }

    /**
     * Determine whether the user can create companies.
     */
    public function create(User $user): bool
    {
        return $user->can('internship.update');
    }

    /**
     * Determine whether the user can update the company.
     */
    public function update(User $user): bool
    {
        return $user->can('internship.update');
    }

    /**
     * Determine whether the user can delete the company.
     */
    public function delete(User $user): bool
    {
        return $user->can('internship.delete');
    }
}
