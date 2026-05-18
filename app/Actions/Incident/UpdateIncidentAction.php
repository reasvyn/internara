<?php

declare(strict_types=1);

namespace App\Actions\Incident;

use App\Actions\Core\LogAuditAction;
use App\Models\IncidentReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UpdateIncidentAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(IncidentReport $incident, array $data): IncidentReport
    {
        $validated = Validator::validate($data, [
            'incident_date' => 'sometimes|date',
            'type' => 'sometimes|string|in:accident,safety_violation,harassment,disciplinary,other',
            'severity' => 'sometimes|string|in:low,medium,high,critical',
            'description' => 'sometimes|string|max:5000',
            'location' => 'nullable|string|max:255',
            'action_taken' => 'nullable|string|max:2000',
            'status' => 'sometimes|string|in:reported,investigating,resolved,closed',
        ]);

        return DB::transaction(function () use ($incident, $validated) {
            $incident->update($validated);

            $this->logAudit->execute(
                action: 'incident_updated',
                subjectType: IncidentReport::class,
                subjectId: $incident->id,
                payload: ['status' => $incident->status->value],
                module: 'Incident',
            );

            return $incident->fresh();
        });
    }
}
