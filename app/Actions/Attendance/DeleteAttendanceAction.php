<?php

declare(strict_types=1);

namespace App\Actions\Attendance;

use App\Actions\Core\LogAuditAction;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;

class DeleteAttendanceAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(Attendance $log): void
    {
        DB::transaction(function () use ($log) {
            $logId = $log->id;
            $userId = $log->user_id;
            $date = $log->date?->toDateString();

            $this->logAudit->execute(
                action: 'attendance_deleted',
                subjectType: Attendance::class,
                subjectId: $logId,
                payload: [
                    'user_id' => $userId,
                    'date' => $date,
                ],
                module: 'Attendance',
            );

            $log->delete();
        });
    }
}
