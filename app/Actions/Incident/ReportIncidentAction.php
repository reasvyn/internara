<?php

declare(strict_types=1);

namespace App\Actions\Incident;

use App\Actions\Core\LogAuditAction;
use App\Enums\Incident\IncidentSeverity;
use App\Models\IncidentReport;
use App\Models\User;
use App\Notifications\IncidentReportedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Exceptions\RoleDoesNotExist;

class ReportIncidentAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(array $data): IncidentReport
    {
        $validated = Validator::validate($data, [
            'registration_id' => 'required|exists:internship_registrations,id',
            'reported_by' => 'required|exists:users,id',
            'incident_date' => 'required|date',
            'type' => 'required|string|in:accident,safety_violation,harassment,disciplinary,other',
            'severity' => 'required|string|in:low,medium,high,critical',
            'description' => 'required|string|max:5000',
            'location' => 'nullable|string|max:255',
            'action_taken' => 'nullable|string|max:2000',
        ]);

        return DB::transaction(function () use ($validated) {
            $incident = IncidentReport::create($validated);

            $this->logAudit->execute(
                action: 'incident_reported',
                subjectType: IncidentReport::class,
                subjectId: $incident->id,
                payload: ['type' => $incident->type->value, 'severity' => $incident->severity->value],
                module: 'Incident',
            );

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
