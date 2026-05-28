<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Actions;

use App\Domain\Attendance\Models\Attendance;
use App\Domain\Core\Actions\BaseAction;

final class DeleteAttendanceAction extends BaseAction
{
    public function execute(Attendance $log): void
    {
        $this->transaction(function () use ($log) {
            $this->log('attendance_deleted', $log, [
                'user_id' => $log->user_id,
                'date' => $log->date?->toDateString(),
            ]);

            $log->delete();
        });
    }
}
