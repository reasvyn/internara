<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\InternshipPlacement;
use App\Models\User;

/**
 * S1 - Secure: Placement deletion blocked if students are registered.
 * S2 - Sustain: Clear authorization rules for internship placements.
 */
class InternshipPlacementPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, InternshipPlacement $placement): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function update(User $user, InternshipPlacement $placement): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function delete(User $user, InternshipPlacement $placement): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin'])
            && !$placement->registrations()->exists();
    }

    public function forceDelete(User $user, InternshipPlacement $placement): bool
    {
        return $user->hasRole('super_admin');
    }
}
