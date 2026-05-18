<?php

declare(strict_types=1);

namespace App\Actions\Report;

use App\Actions\Core\LogAuditAction;
use App\Models\Report;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AddSupervisorReportNotesAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(Report $report, string $notes): Report
    {
        Validator::validate(['notes' => $notes], [
            'notes' => 'required|string|max:5000',
        ]);

        return DB::transaction(function () use ($report, $notes) {
            $report->update(['supervisor_notes' => $notes]);

            $this->logAudit->execute(
                action: 'report_supervisor_notes_added',
                subjectType: Report::class,
                subjectId: $report->id,
                module: 'Report',
            );

            return $report->fresh();
        });
    }
}
