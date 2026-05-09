<?php

declare(strict_types=1);

namespace App\Actions\Attendance;

use App\Actions\Core\LogAuditAction;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;

class VerifyAttendanceAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(Attendance $log): Attendance
    {
        return DB::transaction(function () use ($log) {
            $log->update([
                'is_verified' => true,
                'verified_by' => auth()->id(),
                'verified_at' => now(),
            ]);

            $this->logAudit->execute(
                action: 'attendance_verified',
                subjectType: Attendance::class,
                subjectId: $log->id,
                payload: [
                    'user_id' => $log->user_id,
                    'date' => $log->date?->toDateString(),
                    'status' => $log->status?->value,
                ],
                module: 'Attendance',
            );

            return $log;
        });
    }
}
