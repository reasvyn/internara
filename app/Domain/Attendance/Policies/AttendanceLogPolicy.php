<?php

declare(strict_types=1);

namespace App\Domain\Policies;

use App\Domain\Attendance\Models\AttendanceLog;
use App\Domain\User\Models\User;

/**
 * S1 - Secure: Students can only view their own attendance logs.
 */
class AttendanceLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher', 'supervisor', 'student']);
    }

    public function view(User $user, AttendanceLog $log): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        if (
            $user->hasRole('teacher') &&
            $log->registration &&
            $log->registration->teacher_id === $user->id
        ) {
            return true;
        }

        if (
            $user->hasRole('supervisor') &&
            $log->registration &&
            $log->registration->mentor_id === $user->id
        ) {
            return true;
        }

        return $log->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('student');
    }

    public function verify(User $user, AttendanceLog $log): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher']);
    }

    public function update(User $user, AttendanceLog $log): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function delete(User $user, AttendanceLog $log): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }
}
