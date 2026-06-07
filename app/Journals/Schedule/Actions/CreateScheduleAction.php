<?php

declare(strict_types=1);

namespace App\Journals\Schedule\Actions;

use App\Core\Actions\BaseAction;
use App\Journals\Schedule\Models\Schedule;
use App\User\Models\User;

final class CreateScheduleAction extends BaseAction
{
    public function execute(User $user, array $data): Schedule
    {
        return $this->transaction(function () use ($user, $data) {
            $schedule = Schedule::create([...$data, 'created_by' => $user->id]);

            $this->log('schedule_created', $schedule, ['title' => $schedule->title]);

            return $schedule;
        });
    }
}
