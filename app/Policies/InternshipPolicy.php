<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Internship;
use App\Models\User;

/**
 * S1 - Secure: Internship deletion blocked if placements or registrations exist.
 * S2 - Sustain: Clear authorization rules for internship batches.
 */
class InternshipPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher', 'mentor', 'student']);
    }

    public function view(User $user, Internship $internship): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher', 'mentor', 'student']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function update(User $user, Internship $internship): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function delete(User $user, Internship $internship): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin'])
            && !$internship->placements()->exists()
            && !$internship->registrations()->exists();
    }

    public function forceDelete(User $user, Internship $internship): bool
    {
        return $user->hasRole('super_admin');
    }
}
