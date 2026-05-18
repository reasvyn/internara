<?php

declare(strict_types=1);

namespace App\Actions\Briefing;

use App\Actions\Core\LogAuditAction;
use App\Models\Briefing;
use App\Models\BriefingAttendance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RecordBriefingAttendanceAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(Briefing $briefing, array $attendees): void
    {
        Validator::validate(['attendees' => $attendees], [
            'attendees' => 'required|array|min:1',
            'attendees.*.user_id' => 'required|exists:users,id',
            'attendees.*.attended' => 'required|boolean',
            'attendees.*.notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($briefing, $attendees) {
            foreach ($attendees as $entry) {
                BriefingAttendance::updateOrCreate(
                    ['briefing_id' => $briefing->id, 'user_id' => $entry['user_id']],
                    ['attended' => $entry['attended'], 'notes' => $entry['notes'] ?? null],
                );
            }

            $this->logAudit->execute(
                action: 'briefing_attendance_recorded',
                subjectType: Briefing::class,
                subjectId: $briefing->id,
                payload: ['count' => count($attendees)],
                module: 'Briefing',
            );
        });
    }
}
