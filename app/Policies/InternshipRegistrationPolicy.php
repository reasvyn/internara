<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\InternshipRegistration;
use App\Models\User;

/**
 * S1 - Secure: Students can only view/edit their own registrations.
 */
class InternshipRegistrationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher', 'mentor', 'student']);
    }

    public function view(User $user, InternshipRegistration $registration): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        if ($user->hasRole('teacher') && $registration->teacher_id === $user->id) {
            return true;
        }

        if ($user->hasRole('mentor') && $registration->mentor_id === $user->id) {
            return true;
        }

        return $registration->student_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('student');
    }

    public function update(User $user, InternshipRegistration $registration): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        return $registration->student_id === $user->id && $registration->isPending();
    }

    public function approve(User $user, InternshipRegistration $registration): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function delete(User $user, InternshipRegistration $registration): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        return $registration->student_id === $user->id && $registration->isPending();
    }
}
