<?php

declare(strict_types=1);

namespace Modules\Attendance\Policies;

use Modules\Attendance\Models\AttendanceLog;
use Modules\Permission\Enums\Permission;
use Modules\Permission\Enums\Role;
use Modules\User\Models\User;

/**
 * Class AttendancePolicy
 *
 * Policy for AttendanceLog model operations.
 */
class AttendancePolicy
{
    /**
     * Determine if the user can view any attendance logs.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(Permission::ATTENDANCE_VIEW->value);
    }

    /**
     * Determine if the user can view the attendance log.
     */
    public function view(User $user, AttendanceLog $log): bool
    {
        if (!$user->hasPermissionTo(Permission::ATTENDANCE_VIEW->value)) {
            return false;
        }

        if ($user->id === $log->student_id) {
            return true;
        }

        $registration = $log->registration;

        return $user->id === $registration->teacher_id || $user->id === $registration->mentor_id;
    }

    /**
     * Determine if the user can create attendance records.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo(Permission::ATTENDANCE_CREATE->value);
    }

    /**
     * Determine if the user can update attendance records.
     */
    public function update(User $user, AttendanceLog $log): bool
    {
        if ($user->id === $log->student_id) {
            return $user->hasPermissionTo(Permission::ATTENDANCE_UPDATE->value);
        }

        return $user->hasPermissionTo(Permission::ATTENDANCE_MANAGE->value);
    }

    /**
     * Determine if the user can delete attendance records.
     */
    public function delete(User $user, AttendanceLog $log): bool
    {
        return $user->hasPermissionTo(Permission::ATTENDANCE_MANAGE->value);
    }

    /**
     * Determine if the user can approve attendance records.
     */
    public function approve(User $user, AttendanceLog $log): bool
    {
        return $user->hasPermissionTo(Permission::ATTENDANCE_APPROVE->value);
    }

    /**
     * Determine if the user can force delete.
     */
    public function forceDelete(User $user, AttendanceLog $log): bool
    {
        return $user->hasRole(Role::SUPER_ADMIN->value);
    }
}