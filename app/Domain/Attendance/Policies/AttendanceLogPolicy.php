<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Policies;

use App\Domain\Attendance\Models\AttendanceLog;
use App\Domain\Shared\Policies\BasePolicy;
use App\Domain\User\Models\User;

/**
 * S1 - Secure: Students can only view their own attendance logs.
 */
class AttendanceLogPolicy extends BasePolicy
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

    public function view(User $user, AttendanceLog $log): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if (
            $this->isTeacher($user) &&
            $log->registration &&
            $log->registration->teacher_id === $user->id
        ) {
            return true;
        }

        if (
            $this->isSupervisor($user) &&
            $log->registration &&
            $log->registration->mentor_id === $user->id
        ) {
            return true;
        }

        return $log->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->isStudent($user);
    }

    public function verify(User $user, AttendanceLog $log): bool
    {
        return $this->hasAnyOfRoles($user, [
            'super_admin',
            'admin',
            'teacher',
        ]);
    }

    public function update(User $user, AttendanceLog $log): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, AttendanceLog $log): bool
    {
        return $this->isAdmin($user);
    }
}
