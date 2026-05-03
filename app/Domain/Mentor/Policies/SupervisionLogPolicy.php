<?php

declare(strict_types=1);

namespace App\Domain\Policies;

use App\Domain\Mentor\Models\SupervisionLog;
use App\Domain\User\Models\User;

/**
 * S1 - Secure: Teachers/mentors can only manage logs for their assigned students.
 */
class SupervisionLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher', 'supervisor']);
    }

    public function view(User $user, SupervisionLog $log): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        if ($log->supervisor_id === $user->id) {
            return true;
        }

        $registration = $log->registration;

        if ($user->hasRole('teacher') && $registration && $registration->teacher_id === $user->id) {
            return true;
        }

        if ($user->hasRole('supervisor') && $registration && $registration->mentor_id === $user->id) {
            return true;
        }

        return $registration && $registration->student_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['teacher', 'supervisor']);
    }

    public function update(User $user, SupervisionLog $log): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        return $log->supervisor_id === $user->id && ! $log->is_verified;
    }

    public function verify(User $user, SupervisionLog $log): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher']);
    }

    public function delete(User $user, SupervisionLog $log): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        return $log->supervisor_id === $user->id && ! $log->is_verified;
    }
}
