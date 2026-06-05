<?php

declare(strict_types=1);

namespace App\Reports\Report\Actions;

use App\Core\Actions\BaseAction;
use App\Exceptions\RejectedException;
use App\Reports\Report\Models\Report;
use App\Reports\Report\Models\ReportRevision;

final class RequestReportRevisionAction extends BaseAction
{
    public function execute(Report $report, string $feedback): Report
    {
        if ($report->status->isTerminal()) {
            throw new RejectedException('This report has already been approved.');
        }

        return $this->transaction(function () use ($report, $feedback) {
            $latestRound = $report->revisions()->max('round') ?? 0;

            ReportRevision::create([
                'report_id' => $report->id,
                'round' => $latestRound + 1,
                'feedback' => $feedback,
                'requested_by' => auth()->id(),
                'requested_at' => now(),
            ]);

            $report->update(['status' => 'revision_required']);

            $this->log('report_revision_requested', $report, ['round' => $latestRound + 1]);

            return $report->fresh();
        });
    }
}
