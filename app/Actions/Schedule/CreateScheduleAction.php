<?php

declare(strict_types=1);

namespace App\Actions\Schedule;

use App\Actions\Audit\LogAuditAction;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Creates a new schedule entry.
 *
 * S1 - Secure: Validates user has permission to create schedules.
 */
class CreateScheduleAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(User $user, array $data): Schedule
    {
        return DB::transaction(function () use ($user, $data) {
            $schedule = Schedule::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? '',
                'start_at' => $data['start_at'],
                'end_at' => $data['end_at'] ?? null,
                'type' => $data['type'],
                'location' => $data['location'] ?? null,
                'internship_id' => $data['internship_id'] ?? null,
                'created_by' => $user->id,
            ]);

            $this->logAudit->execute(
                action: 'schedule_created',
                subjectType: Schedule::class,
                subjectId: $schedule->id,
                payload: ['title' => $schedule->title, 'type' => $schedule->type],
                module: 'Schedule',
            );

            return $schedule;
        });
    }
}
