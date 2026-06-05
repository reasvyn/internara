<?php

declare(strict_types=1);

namespace App\Reports\Report\Actions;

use App\Core\Actions\BaseAction;
use App\Core\Exceptions\RejectedException;
use App\Reports\Report\Models\Report;

final class ApproveReportAction extends BaseAction
{
    public function execute(Report $report, array $data): Report
    {
        if ($report->status->isTerminal()) {
            throw new RejectedException('This report has already been approved.');
        }

        return $this->transaction(function () use ($report, $data) {
            $report->update([
                'status' => 'approved',
                'score' => $data['score'] ?? null,
                'feedback' => $data['feedback'] ?? null,
                'graded_by' => auth()->id(),
                'graded_at' => now(),
            ]);

            $this->log('report_approved', $report, ['score' => $data['score'] ?? null]);

            return $report->fresh();
        });
    }
}
