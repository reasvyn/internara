<?php

declare(strict_types=1);

namespace Modules\Schedule\Policies;

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
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('schedule.view') || $user->can('schedule.manage');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Schedule $schedule): bool
    {
        return $user->can('schedule.view') || $user->can('schedule.manage');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('schedule.create') || $user->can('schedule.manage');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Schedule $schedule): bool
    {
        return $user->can('schedule.update') || $user->can('schedule.manage');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Schedule $schedule): bool
    {
        return $user->can('schedule.delete') || $user->can('schedule.manage');
    }
}
