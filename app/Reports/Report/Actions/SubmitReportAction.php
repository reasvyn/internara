<?php

declare(strict_types=1);

namespace App\Reports\Report\Actions;

use App\Core\Actions\BaseAction;
use App\Exceptions\RejectedException;
use App\Reports\Report\Models\Report;

final class SubmitReportAction extends BaseAction
{
    public function execute(Report $report, array $content): Report
    {
        if ($report->status->isTerminal()) {
            throw new RejectedException('This report has already been approved.');
        }

        return $this->transaction(function () use ($report, $content) {
            $report->update([
                'content' => $content,
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            $this->log('report_submitted', $report, ['title' => $report->title]);

            return $report->fresh();
        });
    }
}
