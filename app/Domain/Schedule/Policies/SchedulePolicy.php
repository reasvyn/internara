<?php

declare(strict_types=1);

namespace App\Domain\Schedule\Policies;

use App\Domain\Schedule\Models\Schedule;
use App\Domain\Shared\Policies\BasePolicy;
use App\Domain\User\Models\User;

/**
 * S1 - Secure: Schedule management restricted to authorized roles.
 */
class SchedulePolicy extends BasePolicy
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

    public function view(User $user, Schedule $schedule): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->hasAnyOfRoles($user, [
            'super_admin',
            'admin',
            'teacher',
        ]);
    }

    public function update(User $user, Schedule $schedule): bool
    {
        return $this->isAdmin($user) || $schedule->created_by === $user->id;
    }

    public function delete(User $user, Schedule $schedule): bool
    {
        return $this->isAdmin($user);
    }
}
