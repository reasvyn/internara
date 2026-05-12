<?php

declare(strict_types=1);

namespace App\Actions\Schedule;

use App\Actions\Core\LogAuditAction;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteScheduleAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(User $user, Schedule $schedule): void
    {
        DB::transaction(function () use ($schedule) {
            $scheduleId = $schedule->id;
            $scheduleTitle = $schedule->title;

            $schedule->delete();

            $this->logAudit->execute(
                action: 'schedule_deleted',
                subjectType: Schedule::class,
                subjectId: $scheduleId,
                payload: ['title' => $scheduleTitle],
                module: 'Schedule',
            );
        });
    }
}
