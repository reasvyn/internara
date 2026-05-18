<?php

declare(strict_types=1);

namespace App\Actions\Briefing;

use App\Actions\Core\LogAuditAction;
use App\Models\BriefingAttendance;
use Illuminate\Support\Facades\DB;

class OverrideBriefingAttendanceAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(BriefingAttendance $attendance, bool $attended): BriefingAttendance
    {
        return DB::transaction(function () use ($attendance, $attended) {
            $attendance->update(['attended' => $attended]);

            $this->logAudit->execute(
                action: 'briefing_attendance_override',
                subjectType: BriefingAttendance::class,
                subjectId: $attendance->id,
                payload: ['attended' => $attended],
                module: 'Briefing',
            );

            return $attendance->fresh();
        });
    }
}
