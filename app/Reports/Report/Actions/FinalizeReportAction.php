<?php

declare(strict_types=1);

namespace App\Reports\Report\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Core\Exceptions\RejectedException;
use App\Reports\Report\Enums\ReportStatus;
use App\Reports\Report\Events\ReportFinalized;
use App\Reports\Report\Models\Report;

final class FinalizeReportAction extends BaseCommandAction
{
    public function execute(Report $report, string $finalizedBy): Report
    {
        if ($report->status->isTerminal()) {
            throw new RejectedException('This report has already been finalized.');
        }

        return $this->transaction(function () use ($report, $finalizedBy) {
            $report->update([
                'status' => ReportStatus::FINALIZED->value,
                'finalized_by' => $finalizedBy,
                'finalized_at' => now(),
            ]);

            $report->captureSnapshot();

            if ($report->isDirty()) {
                $report->saveQuietly();
            }

            $this->log('report_finalized', $report, [
                'final_score' => $report->final_score,
                'grade_letter' => $report->grade_letter,
            ]);

            $this->dispatchEvent(new ReportFinalized($report));

            return $report->fresh();
        });
    }
}
