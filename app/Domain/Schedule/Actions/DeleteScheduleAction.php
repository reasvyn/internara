<?php

declare(strict_types=1);

namespace App\Domain\Schedule\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Schedule\Models\Schedule;
use App\Domain\User\Models\User;

class DeleteScheduleAction extends BaseAction
{
    public function execute(User $user, Schedule $schedule): void
    {
        $this->transaction(function () use ($schedule) {
            $scheduleId = $schedule->id;
            $scheduleTitle = $schedule->title;

            $schedule->delete();

            $this->log('schedule_deleted', $scheduleId, ['title' => $scheduleTitle]);
        });
    }
}
