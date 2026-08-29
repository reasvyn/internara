<?php

declare(strict_types=1);

namespace App\Modules\Journals\Domain\Attendance\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Journals\Domain\Attendance\Models\Attendance;

final class DeleteAttendanceAction extends BaseCommandAction
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
