<?php

declare(strict_types=1);

namespace App\Actions\Incident;

use App\Actions\Core\LogAuditAction;
use App\Models\IncidentReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class ResolveIncidentAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(IncidentReport $incident, array $data): IncidentReport
    {
        if ($incident->status->isTerminal()) {
            throw new RuntimeException('This incident is already closed.');
        }

        $validated = Validator::validate($data, [
            'resolution_notes' => 'required|string|max:5000',
            'status' => 'required|string|in:resolved,closed',
        ]);

        return DB::transaction(function () use ($incident, $validated) {
            $incident->update([
                'status' => $validated['status'],
                'resolved_at' => now(),
                'resolved_by' => auth()->id(),
                'resolution_notes' => $validated['resolution_notes'],
            ]);

            $this->logAudit->execute(
                action: 'incident_resolved',
                subjectType: IncidentReport::class,
                subjectId: $incident->id,
                payload: ['status' => $validated['status']],
                module: 'Incident',
            );

            return $incident->fresh();
        });
    }
}
