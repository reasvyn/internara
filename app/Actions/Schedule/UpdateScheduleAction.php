<?php

declare(strict_types=1);

namespace App\Actions\Schedule;

use App\Models\Schedule;
use App\Models\User;

/**
 * Updates an existing schedule entry.
 */
class UpdateScheduleAction
{
    public function execute(User $user, Schedule $schedule, array $data): Schedule
    {
        $schedule->update([
            'title' => $data['title'] ?? $schedule->title,
            'description' => $data['description'] ?? $schedule->description,
            'start_at' => $data['start_at'] ?? $schedule->start_at,
            'end_at' => $data['end_at'] ?? $schedule->end_at,
            'type' => $data['type'] ?? $schedule->type,
            'location' => $data['location'] ?? $schedule->location,
        ]);

        return $schedule;
    }
}
