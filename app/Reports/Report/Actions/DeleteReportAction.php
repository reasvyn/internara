<?php

declare(strict_types=1);

namespace App\Reports\Report\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Core\Exceptions\RejectedException;
use App\Reports\Report\Models\Report;

final class DeleteReportAction extends BaseCommandAction
{
    public function execute(Report $report): void
    {
        if ($report->status->isTerminal()) {
            throw new RejectedException(__('reports.cannot_delete_finalized'));
        }

        $this->authorize('delete', $report);

        $this->transaction(function () use ($report) {
            $report->delete();
        });

        $this->log('report_deleted', $report, [
            'registration_id' => $report->registration_id,
        ]);
    }
}
