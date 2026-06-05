<?php

declare(strict_types=1);

namespace App\Journals\Attendance\Actions;

use App\Core\Actions\BaseAction;
use App\Journals\Attendance\Models\Attendance;

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
