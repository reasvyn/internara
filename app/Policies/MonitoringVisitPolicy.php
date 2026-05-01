<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MonitoringVisit;
use App\Models\User;

/**
 * S1 - Secure: Teachers can only manage visits for their assigned students.
 */
class MonitoringVisitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher', 'mentor']);
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

        if ($user->hasRole('mentor') && $registration && $registration->mentor_id === $user->id) {
            return true;
        }

        return $registration && $registration->student_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['teacher', 'mentor']);
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
