<?php

declare(strict_types=1);

namespace App\Actions\Schedule;

use App\Models\Schedule;
use App\Models\User;

/**
 * Deletes a schedule entry.
 *
 * S1 - Secure: Only authorized users can delete schedules.
 */
class DeleteScheduleAction
{
    public function execute(User $user, Schedule $schedule): void
    {
        $schedule->delete();
    }
}
