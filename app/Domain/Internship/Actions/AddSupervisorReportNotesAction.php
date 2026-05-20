<?php

declare(strict_types=1);

namespace App\Domain\Internship\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Internship\Models\Report;
use Illuminate\Support\Facades\Validator;

class AddSupervisorReportNotesAction extends BaseAction
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
