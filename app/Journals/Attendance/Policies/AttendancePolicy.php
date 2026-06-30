<?php

declare(strict_types=1);

namespace App\Journals\Attendance\Policies;

use App\Core\Policies\BasePolicy;
use App\Journals\Attendance\Models\Attendance;
use App\User\Models\User;
use App\User\Policies\Concerns\HasMentorProxy;

class AttendancePolicy extends BasePolicy
{
    use HasMentorProxy;
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

        if ($log->user_id === $user->id) {
            return true;
        }

        return $this->mentorProxyFor($log->registration, $user)?->canVerifyAttendance($user) ?? false;
    }

    public function create(User $user): bool
    {
        return $this->isStudent($user);
    }

    public function verify(User $user, Attendance $log): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $this->mentorProxyFor($log->registration, $user)?->canVerifyAttendance($user) ?? false;
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
