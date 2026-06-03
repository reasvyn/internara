<?php

declare(strict_types=1);

namespace App\Domain\Journals\Aggregates\Schedule\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Journals\Aggregates\Schedule\Models\Schedule;
use App\Domain\User\Models\User;

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
