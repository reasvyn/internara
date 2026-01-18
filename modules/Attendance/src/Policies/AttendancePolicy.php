<?php

declare(strict_types=1);

namespace Modules\Attendance\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Attendance\Models\AttendanceLog;
use Modules\User\Models\User;

class AttendancePolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view the attendance log.
     */
    public function view(User $user, AttendanceLog $log): bool
    {
        // Student can view their own
        if ($user->id === $log->student_id) {
            return true;
        }

        // Teacher or Mentor assigned to this registration can view
        $registration = $log->registration;

        return $user->id === $registration->teacher_id || $user->id === $registration->mentor_id;
    }

    /**
     * Determine if the user can view any attendance logs.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['student', 'teacher', 'mentor', 'admin']);
    }
}
