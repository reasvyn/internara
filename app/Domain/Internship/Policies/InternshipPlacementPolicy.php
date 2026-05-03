<?php

declare(strict_types=1);

namespace App\Domain\Policies;

use App\Domain\Internship\Models\Placement;
use App\Domain\User\Models\User;

/**
 * S1 - Secure: Placement deletion blocked if students are registered.
 * S2 - Sustain: Clear authorization rules for internship placements.
 */
class PlacementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher', 'supervisor', 'student']);
    }

    public function view(User $user, Placement $placement): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher', 'supervisor', 'student']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function update(User $user, Placement $placement): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function delete(User $user, Placement $placement): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']) &&
            ! $placement->registrations()->exists();
    }

    public function forceDelete(User $user, Placement $placement): bool
    {
        return $user->hasRole('super_admin');
    }
}
