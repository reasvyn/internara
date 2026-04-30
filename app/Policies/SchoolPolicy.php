<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\School;
use App\Models\User;

/**
 * S1 - Secure: School deletion restricted to Super Admin only.
 * S2 - Sustain: Clear authorization rules for institutional metadata.
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
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ?School $school = null): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    /**
     * Determine whether the user can delete the model.
     * Deletion of institutional record is restricted to Super Admin only.
     */
    public function delete(User $user, ?School $school = null): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can force delete the model.
     */
    public function forceDelete(User $user, ?School $school = null): bool
    {
        return $user->hasRole('super_admin');
    }
}
