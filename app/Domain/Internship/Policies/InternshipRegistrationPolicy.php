<?php

declare(strict_types=1);

namespace App\Domain\Policies;

use App\Domain\Internship\Models\Registration;
use App\Domain\User\Models\User;

/**
 * S1 - Secure: Students can only view/edit their own registrations.
 */
class RegistrationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher', 'supervisor', 'student']);
    }

    public function view(User $user, Registration $registration): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        if ($user->hasRole('teacher') && $registration->teacher_id === $user->id) {
            return true;
        }

        if ($user->hasRole('supervisor') && $registration->mentor_id === $user->id) {
            return true;
        }

        return $registration->student_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('student');
    }

    public function update(User $user, Registration $registration): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        return $registration->student_id === $user->id && $registration->isPending();
    }

    public function approve(User $user, Registration $registration): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function delete(User $user, Registration $registration): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        return $registration->student_id === $user->id && $registration->isPending();
    }
}
