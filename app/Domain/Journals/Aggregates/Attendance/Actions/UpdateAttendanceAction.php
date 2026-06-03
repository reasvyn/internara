<?php

declare(strict_types=1);

namespace App\Domain\Journals\Aggregates\Attendance\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Journals\Aggregates\Attendance\Models\Attendance;

final class UpdateAttendanceAction extends BaseAction
{
    public function execute(Attendance $log, array $data): Attendance
    {
        return $this->transaction(function () use ($log, $data) {
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

            $this->log('attendance_updated', $log, [
                'user_id' => $log->user_id,
                'date' => $log->date?->toDateString(),
                'status' => $log->status?->value,
            ]);

            return $log;
        });
    }
}
