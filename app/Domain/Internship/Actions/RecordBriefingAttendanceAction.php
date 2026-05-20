<?php

declare(strict_types=1);

namespace App\Domain\Internship\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Internship\Models\Briefing;
use App\Domain\Internship\Models\BriefingAttendance;
use Illuminate\Support\Facades\Validator;

class RecordBriefingAttendanceAction extends BaseAction
{
    public function execute(Briefing $briefing, array $attendees): void
    {
        Validator::validate(['attendees' => $attendees], [
            'attendees' => 'required|array|min:1',
            'attendees.*.user_id' => 'required|exists:users,id',
            'attendees.*.attended' => 'required|boolean',
            'attendees.*.notes' => 'nullable|string|max:1000',
        ]);

        $this->transaction(function () use ($briefing, $attendees) {
            foreach ($attendees as $entry) {
                BriefingAttendance::updateOrCreate(
                    ['briefing_id' => $briefing->id, 'user_id' => $entry['user_id']],
                    ['attended' => $entry['attended'], 'notes' => $entry['notes'] ?? null],
                );
            }

            $this->log('briefing_attendance_recorded', $briefing, ['count' => count($attendees)]);
        });
    }
}
