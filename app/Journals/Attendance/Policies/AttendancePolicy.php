<?php

declare(strict_types=1);

namespace App\Journals\Attendance\Policies;

use App\Core\Policies\BasePolicy;
use App\Guidance\Mentor\Models\Mentor;
use App\Journals\Attendance\Models\Attendance;
use App\User\Models\User;

/**
 * S1 - Secure: Students can only view their own attendance logs.
 */
class AttendancePolicy extends BasePolicy
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
            $log->registration->mentors()
                ->where('user_id', $user->id)
                ->where('type', Mentor::TYPE_SCHOOL_TEACHER)
                ->exists()
        ) {
            return true;
        }

        if (
            $this->isSupervisor($user) &&
            $log->registration &&
            $log->registration->mentors()
                ->where('user_id', $user->id)
                ->where('type', Mentor::TYPE_INDUSTRY_SUPERVISOR)
                ->exists()
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
