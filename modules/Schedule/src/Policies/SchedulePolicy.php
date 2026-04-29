<?php

declare(strict_types=1);

namespace Modules\Schedule\Policies;

use Modules\Permission\Enums\Permission;
use Modules\Permission\Enums\Role;
use Modules\Schedule\Models\Schedule;
use Modules\User\Models\User;

/**
 * Class SchedulePolicy
 *
 * Controls access to Schedule model operations.
 */
class SchedulePolicy
{
    /**
     * Determine whether the user can view any schedules.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            Permission::SCHEDULE_VIEW->value,
            Permission::SCHEDULE_MANAGE->value,
        ]);
    }

    /**
     * Determine whether the user can view the schedule.
     */
    public function view(User $user, Schedule $schedule): bool
    {
        return $user->hasAnyPermission([
            Permission::SCHEDULE_VIEW->value,
            Permission::SCHEDULE_MANAGE->value,
        ]);
    }

    /**
     * Determine whether the user can create schedules.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo(Permission::SCHEDULE_MANAGE->value);
    }

    /**
     * Determine whether the user can update the schedule.
     */
    public function update(User $user, Schedule $schedule): bool
    {
        return $user->hasPermissionTo(Permission::SCHEDULE_MANAGE->value);
    }

    /**
     * Determine whether the user can delete the schedule.
     */
    public function delete(User $user, Schedule $schedule): bool
    {
        return $user->hasPermissionTo(Permission::SCHEDULE_MANAGE->value);
    }

    /**
     * Determine whether the user can force delete.
     */
    public function forceDelete(User $user, Schedule $schedule): bool
    {
        return $user->hasRole(Role::SUPER_ADMIN->value);
    }
}