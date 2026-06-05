<?php

declare(strict_types=1);

namespace App\Journals\Schedule\Actions;

use App\Core\Actions\BaseAction;
use App\Journals\Schedule\Models\Schedule;
use App\User\Models\User;

final class DeleteScheduleAction extends BaseAction
{
    public function execute(User $user, Schedule $schedule): void
    {
        $this->transaction(function () use ($schedule) {
            $scheduleTitle = $schedule->title;

            $schedule->delete();

            $this->log('schedule_deleted', $schedule, ['title' => $scheduleTitle]);
        });
    }
}
