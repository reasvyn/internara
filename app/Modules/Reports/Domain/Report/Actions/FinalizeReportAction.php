<?php

declare(strict_types=1);

namespace App\Modules\Reports\Domain\Report\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Reports\Domain\Report\Enums\ReportStatus;
use App\Modules\Reports\Domain\Report\Events\ReportFinalized;
use App\Modules\Reports\Domain\Report\Models\Report;

final class FinalizeReportAction extends BaseCommandAction
{
    public function execute(Report $report, string $finalizedBy): Report
    {
        if ($report->status->isTerminal()) {
            throw new RejectedException(__('report.already_finalized'));
        }

        return $this->transaction(function () use ($report, $finalizedBy) {
            $report->update([
                'status' => ReportStatus::FINALIZED->value,
                'finalized_by' => $finalizedBy,
                'finalized_at' => now(),
            ]);

            $this->log('report_finalized', $report, [
                'final_score' => $report->final_score,
                'grade_letter' => $report->grade_letter,
            ]);

            $this->dispatchEvent(new ReportFinalized($report));

            return $report->fresh();
        });
    }
}
