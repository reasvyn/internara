<?php

declare(strict_types=1);

namespace App\Domain\Schedule\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Schedule\Models\Schedule;
use App\Domain\User\Models\User;

class UpdateScheduleAction extends BaseAction
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
