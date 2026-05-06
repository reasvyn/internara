<?php

declare(strict_types=1);

namespace App\Domain\Mentor\Policies;

use App\Domain\Mentor\Models\MonitoringVisit;
use App\Domain\Shared\Policies\BasePolicy;
use App\Domain\User\Models\User;

/**
 * S1 - Secure: Teachers can only manage visits for their assigned students.
 */
class MonitoringVisitPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasAnyOfRoles($user, [
            'super_admin',
            'admin',
            'teacher',
            'supervisor',
        ]);
    }

    public function view(User $user, MonitoringVisit $visit): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        $registration = $visit->registration;

        if ($visit->teacher_id === $user->id) {
            return true;
        }

        if ($this->isTeacher($user) && $registration && $registration->teacher_id === $user->id) {
            return true;
        }

        if ($this->isSupervisor($user) && $registration && $registration->mentor_id === $user->id) {
            return true;
        }

        return $registration && $registration->student_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->hasAnyOfRoles($user, [
            'teacher',
            'supervisor',
        ]);
    }

    public function update(User $user, MonitoringVisit $visit): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $visit->teacher_id === $user->id;
    }

    public function delete(User $user, MonitoringVisit $visit): bool
    {
        return $this->isAdmin($user);
    }
}
