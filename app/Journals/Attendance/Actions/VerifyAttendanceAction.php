<?php

declare(strict_types=1);

namespace App\Journals\Attendance\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Journals\Attendance\Models\Attendance;

final class VerifyAttendanceAction extends BaseCommandAction
{
    public function execute(Attendance $log): Attendance
    {
        return $this->transaction(function () use ($log) {
            $log->update([
                'is_verified' => true,
                'verified_by' => auth()->id(),
                'verified_at' => now(),
            ]);

            $this->log('attendance_verified', $log, [
                'user_id' => $log->user_id,
                'date' => $log->date?->toDateString(),
                'status' => $log->status?->value,
            ]);

            return $log;
        });
    }
}
