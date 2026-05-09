<?php

declare(strict_types=1);

namespace App\Actions\Attendance;

use App\Actions\Core\LogAuditAction;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;

class UpdateAttendanceAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(Attendance $log, array $data): Attendance
    {
        return DB::transaction(function () use ($log, $data) {
            $updateData = array_filter([
                'date' => $data['date'] ?? null,
                'clock_in' => $data['clock_in'] ?? null,
                'clock_out' => $data['clock_out'] ?? null,
                'status' => $data['status'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_verified' => $data['is_verified'] ?? null,
                'verified_by' => isset($data['is_verified']) && $data['is_verified'] ? auth()->id() : null,
                'verified_at' => isset($data['is_verified']) && $data['is_verified'] ? now() : null,
            ], fn ($v) => $v !== null);

            if ($updateData !== []) {
                $log->update($updateData);
            }

            $this->logAudit->execute(
                action: 'attendance_updated',
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
