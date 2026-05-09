<?php

declare(strict_types=1);

namespace App\Policies\Attendance;

use App\Models\Attendance;
use App\Models\User;
use App\Policies\Shared\BasePolicy;

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

    public function view(User $user, Attendance $log): bool
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

    public function verify(User $user, Attendance $log): bool
    {
        return $this->hasAnyOfRoles($user, [
            'super_admin',
            'admin',
            'teacher',
        ]);
    }

    public function update(User $user, Attendance $log): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, Attendance $log): bool
    {
        return $this->isAdmin($user);
    }
}
