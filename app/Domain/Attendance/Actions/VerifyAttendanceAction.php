<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Actions;

use App\Domain\Attendance\Models\Attendance;
use App\Domain\Core\Actions\BaseAction;

class VerifyAttendanceAction extends BaseAction
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
