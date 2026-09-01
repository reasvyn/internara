<?php

declare(strict_types=1);

namespace App\Modules\Reports\Domain\StudentReport\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Reports\Domain\StudentReport\Models\StudentReport;

final class DeleteStudentReportAction extends BaseCommandAction
{
    public function execute(StudentReport $report): void
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
