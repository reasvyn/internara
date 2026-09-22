<?php

declare(strict_types=1);

namespace App\Modules\Incident\Domain\IncidentReport\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Incident\Domain\IncidentReport\Enums\IncidentStatus;
use App\Modules\Incident\Domain\IncidentReport\Models\IncidentReport;
use Illuminate\Support\Facades\Validator;

final class UpdateIncidentAction extends BaseCommandAction
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
            if (isset($validated['status'])) {
                $targetStatus = IncidentStatus::from($validated['status']);
                if ($incident->status !== $targetStatus && ! $incident->status->canTransitionTo($targetStatus)) {
                    throw new RejectedException(__('incident.illegal_transition', [
                        'from' => $incident->status->label(),
                        'to' => $targetStatus->label(),
                    ]));
                }
            }

            $incident->update($validated);

            $this->log('incident_updated', $incident, ['status' => $incident->status->value]);

            return $incident->fresh();
        });
    }
}
