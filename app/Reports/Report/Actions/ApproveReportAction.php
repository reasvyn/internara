<?php

declare(strict_types=1);

namespace App\Reports\Report\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Core\Exceptions\RejectedException;
use App\Reports\Report\Enums\ReportStatus;
use App\Reports\Report\Events\ReportApproved;
use App\Reports\Report\Models\Report;

final class ApproveReportAction extends BaseCommandAction
{
    public function __construct(protected readonly CalculateFinalGradeAction $calculateGrade) {}

    public function execute(Report $report, array $data): Report
    {
        if ($report->status->isTerminal()) {
            throw new RejectedException('This report has already been approved.');
        }

        return $this->transaction(function () use ($report, $data) {
            $report = $this->calculateGrade->execute($report);

            $report->update([
                'status' => ReportStatus::APPROVED->value,
                'industry_feedback' => $data['feedback'] ?? null,
            ]);

            $this->log('report_approved', $report, [
                'final_score' => $report->final_score,
                'grade_letter' => $report->grade_letter,
            ]);

            event(new ReportApproved($report));

            return $report->fresh();
        });
    }
}
