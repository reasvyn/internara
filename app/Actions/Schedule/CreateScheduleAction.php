<?php

declare(strict_types=1);

namespace App\Actions\Schedule;

use App\Actions\Core\LogAuditAction;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateScheduleAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(User $user, array $data): Schedule
    {
        return DB::transaction(function () use ($user, $data) {
            $schedule = Schedule::create([
                ...$data,
                'created_by' => $user->id,
            ]);

            $this->logAudit->execute(
                action: 'schedule_created',
                subjectType: Schedule::class,
                subjectId: $schedule->id,
                payload: ['title' => $schedule->title],
                module: 'Schedule',
            );

            return $schedule;
        });
    }
}
