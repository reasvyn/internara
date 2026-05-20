<?php

declare(strict_types=1);

namespace App\Domain\Internship\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Internship\Models\BriefingAttendance;

class OverrideBriefingAttendanceAction extends BaseAction
{
    public function execute(BriefingAttendance $attendance, bool $attended): BriefingAttendance
    {
        return $this->transaction(function () use ($attendance, $attended) {
            $attendance->update(['attended' => $attended]);

            $this->log('briefing_attendance_override', $attendance, ['attended' => $attended]);

            return $attendance->fresh();
        });
    }
}
