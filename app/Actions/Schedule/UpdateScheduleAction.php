<?php

declare(strict_types=1);

namespace App\Actions\Schedule;

use App\Actions\Core\LogAuditAction;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateScheduleAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(User $user, Schedule $schedule, array $data): Schedule
    {
        return DB::transaction(function () use ($schedule, $data) {
            $schedule->update($data);

            $this->logAudit->execute(
                action: 'schedule_updated',
                subjectType: Schedule::class,
                subjectId: $schedule->id,
                payload: ['title' => $schedule->title],
                module: 'Schedule',
            );

            return $schedule;
        });
    }
}
