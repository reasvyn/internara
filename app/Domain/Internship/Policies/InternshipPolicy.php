<?php

declare(strict_types=1);

namespace App\Domain\Internship\Policies;

use App\Domain\Internship\Models\Internship;
use App\Domain\Shared\Policies\BasePolicy;
use App\Domain\User\Models\User;

/**
 * S1 - Secure: Internship deletion blocked if placements or registrations exist.
 * S2 - Sustain: Clear authorization rules for internship batches.
 */
class InternshipPolicy extends BasePolicy
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

    public function view(User $user, Internship $internship): bool
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

    public function update(User $user, Internship $internship): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, Internship $internship): bool
    {
        return $this->isAdmin($user) &&
            ! $internship->placements()->exists() &&
            ! $internship->registrations()->exists();
    }

    public function forceDelete(User $user, Internship $internship): bool
    {
        return $user->hasRole('super_admin');
    }
}
