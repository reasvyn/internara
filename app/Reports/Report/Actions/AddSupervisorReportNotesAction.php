<?php

declare(strict_types=1);

namespace App\Reports\Report\Actions;

use App\Core\Actions\BaseAction;
use App\Reports\Report\Models\Report;
use Illuminate\Support\Facades\Validator;

final class AddSupervisorReportNotesAction extends BaseAction
{
    public function execute(Report $report, string $notes): Report
    {
        Validator::validate(['notes' => $notes], [
            'notes' => 'required|string|max:5000',
        ]);

        return $this->transaction(function () use ($report, $notes) {
            $report->update(['supervisor_notes' => $notes]);

            $this->log('report_supervisor_notes_added', $report);

            return $report->fresh();
        });
    }
}
