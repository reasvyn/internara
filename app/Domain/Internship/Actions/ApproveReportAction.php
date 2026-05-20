<?php

declare(strict_types=1);

namespace App\Domain\Internship\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Internship\Models\Report;

class ApproveReportAction extends BaseAction
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
