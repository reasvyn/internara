<?php

declare(strict_types=1);

namespace App\Journals\Schedule\Actions;

use App\Core\Actions\BaseAction;
use App\Journals\Schedule\Models\Schedule;
use App\User\Models\User;

final class UpdateScheduleAction extends BaseAction
{
    public function execute(User $user, Schedule $schedule, array $data): Schedule
    {
        return $this->transaction(function () use ($schedule, $data) {
            $schedule->update($data);

            $this->log('schedule_updated', $schedule, ['title' => $schedule->title]);

            return $schedule;
        });
    }
}
