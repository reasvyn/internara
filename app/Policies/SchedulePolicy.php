<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Schedule;
use App\Models\User;

/**
 * S1 - Secure: Schedule management restricted to authorized roles.
 */
class SchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher', 'mentor']);
    }

    public function view(User $user, Schedule $schedule): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher']);
    }

    public function update(User $user, Schedule $schedule): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']) || $schedule->created_by === $user->id;
    }

    public function delete(User $user, Schedule $schedule): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }
}
