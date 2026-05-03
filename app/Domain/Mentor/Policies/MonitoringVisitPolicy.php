<?php

declare(strict_types=1);

namespace App\Domain\Policies;

use App\Domain\Mentor\Models\MonitoringVisit;
use App\Domain\User\Models\User;

/**
 * S1 - Secure: Teachers can only manage visits for their assigned students.
 */
class MonitoringVisitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher', 'supervisor']);
    }

    public function view(User $user, MonitoringVisit $visit): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        $registration = $visit->registration;

        if ($visit->teacher_id === $user->id) {
            return true;
        }

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

    public function update(User $user, MonitoringVisit $visit): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        return $visit->teacher_id === $user->id;
    }

    public function delete(User $user, MonitoringVisit $visit): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }
}
