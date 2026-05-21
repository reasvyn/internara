<?php

declare(strict_types=1);

namespace App\Domain\Incident\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Incident\Enums\IncidentSeverity;
use App\Domain\Incident\Models\IncidentReport;
use App\Domain\Incident\Notifications\IncidentReportedNotification;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Exceptions\RoleDoesNotExist;

class ReportIncidentAction extends BaseAction
{
    public function execute(array $data): IncidentReport
    {
        $validated = Validator::validate($data, [
            'registration_id' => 'required|exists:registrations,id',
            'reported_by' => 'required|exists:users,id',
            'incident_date' => 'required|date',
            'type' => 'required|string|in:accident,safety_violation,harassment,disciplinary,other',
            'severity' => 'required|string|in:low,medium,high,critical',
            'description' => 'required|string|max:5000',
            'location' => 'nullable|string|max:255',
            'action_taken' => 'nullable|string|max:2000',
        ]);

        return $this->transaction(function () use ($validated) {
            $incident = IncidentReport::create($validated);

            $this->log('incident_reported', $incident, ['type' => $incident->type->value, 'severity' => $incident->severity->value]);

            if (in_array($incident->severity, [IncidentSeverity::HIGH, IncidentSeverity::CRITICAL], true)) {
                try {
                    $admins = User::role(['super_admin', 'admin'])->get();
                    $teacher = $incident->registration?->mentee?->user;

                    foreach ($admins as $admin) {
                        $admin->notify(new IncidentReportedNotification($incident));
                    }

                    if ($teacher) {
                        $teacher->notify(new IncidentReportedNotification($incident));
                    }
                } catch (RoleDoesNotExist) {

                }
            }

            return $incident;
        });
    }
}
