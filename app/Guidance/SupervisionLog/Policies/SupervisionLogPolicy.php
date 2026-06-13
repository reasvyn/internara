<?php

declare(strict_types=1);

namespace App\Guidance\SupervisionLog\Policies;

use App\Core\Policies\BasePolicy;
use App\Guidance\SupervisionLog\Models\SupervisionLog;
use App\User\Models\User;

/**
 * S1 - Secure: Teachers/mentors can only manage logs for their assigned students.
 */
class SupervisionLogPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasAnyOfRoles($user, ['super_admin', 'admin', 'teacher', 'supervisor']);
    }

    public function view(User $user, SupervisionLog $log): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if ($log->supervisor_id === $user->id) {
            return true;
        }

        $registration = $log->registration;

        if (
            $this->isTeacher($user) &&
            $registration &&
            $registration
                ->mentors()
                ->where('user_id', $user->id)
                ->where('internship_group_members.role', 'teacher')
                ->exists()
        ) {
            return true;
        }

        if (
            $this->isSupervisor($user) &&
            $registration &&
            $registration
                ->mentors()
                ->where('user_id', $user->id)
                ->where('internship_group_members.role', 'supervisor')
                ->exists()
        ) {
            return true;
        }

        return $registration && $registration->student_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->hasAnyOfRoles($user, ['teacher', 'supervisor']);
    }

    public function update(User $user, SupervisionLog $log): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $log->supervisor_id === $user->id && ! $log->is_verified;
    }

    public function verify(User $user, SupervisionLog $log): bool
    {
        return $this->hasAnyOfRoles($user, ['super_admin', 'admin', 'teacher']);
    }

    public function delete(User $user, SupervisionLog $log): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $log->supervisor_id === $user->id && ! $log->is_verified;
    }
}
