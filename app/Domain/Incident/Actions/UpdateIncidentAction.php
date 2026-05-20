<?php

declare(strict_types=1);

namespace App\Domain\Incident\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Incident\Models\IncidentReport;
use Illuminate\Support\Facades\Validator;

class UpdateIncidentAction extends BaseAction
{
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

        return $this->transaction(function () use ($incident, $validated) {
            $incident->update($validated);

            $this->log('incident_updated', $incident, ['status' => $incident->status->value]);

            return $incident->fresh();
        });
    }
}
