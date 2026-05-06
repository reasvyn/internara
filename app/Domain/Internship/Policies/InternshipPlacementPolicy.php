<?php

declare(strict_types=1);

namespace App\Domain\Internship\Policies;

use App\Domain\Internship\Models\Placement;
use App\Domain\Shared\Policies\BasePolicy;
use App\Domain\User\Models\User;

/**
 * S1 - Secure: Placement deletion blocked if students are registered.
 * S2 - Sustain: Clear authorization rules for internship placements.
 */
class PlacementPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasAnyOfRoles($user, [
            'super_admin',
            'admin',
            'teacher',
            'supervisor',
            'student',
        ]);
    }

    public function view(User $user, Placement $placement): bool
    {
        return $this->hasAnyOfRoles($user, [
            'super_admin',
            'admin',
            'teacher',
            'supervisor',
            'student',
        ]);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, Placement $placement): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, Placement $placement): bool
    {
        return $this->isAdmin($user) && ! $placement->registrations()->exists();
    }

    public function forceDelete(User $user, Placement $placement): bool
    {
        return $user->hasRole('super_admin');
    }
}
