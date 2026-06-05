<?php

declare(strict_types=1);

namespace App\Incident\IncidentReport\Actions;

use App\Core\Actions\BaseAction;
use App\Incident\IncidentReport\Models\IncidentReport;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

final class ResolveIncidentAction extends BaseAction
{
    public function execute(IncidentReport $incident, array $data): IncidentReport
    {
        if ($incident->status->isTerminal()) {
            throw new RuntimeException('This incident is already closed.');
        }

        $validated = Validator::validate($data, [
            'resolution_notes' => 'required|string|max:5000',
            'status' => 'required|string|in:resolved,closed',
        ]);

        return $this->transaction(function () use ($incident, $validated) {
            $incident->update([
                'status' => $validated['status'],
                'resolved_at' => now(),
                'resolved_by' => auth()->id(),
                'resolution_notes' => $validated['resolution_notes'],
            ]);

            $this->log('incident_resolved', $incident, ['status' => $validated['status']]);

            return $incident->fresh();
        });
    }
}
