<?php

declare(strict_types=1);

namespace App\Domain\Schedule\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Schedule\Models\Schedule;
use App\Domain\User\Models\User;

class CreateScheduleAction extends BaseAction
{
    public function execute(User $user, array $data): Schedule
    {
        return $this->transaction(function () use ($user, $data) {
            $schedule = Schedule::create([
                ...$data,
                'created_by' => $user->id,
            ]);

            $this->log('schedule_created', $schedule, ['title' => $schedule->title]);

            return $schedule;
        });
    }
}
