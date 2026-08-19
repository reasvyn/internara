<?php

declare(strict_types=1);

namespace App\Reports\Report\Observers;

use App\Reports\Report\Actions\CaptureReportSnapshotAction;
use App\Reports\Report\Enums\ReportStatus;
use App\Reports\Report\Models\Report;

class ReportObserver
{
    public function __construct(
        private readonly CaptureReportSnapshotAction $captureSnapshot,
    ) {}

    public function saved(Report $report): void
    {
        if ($report->status === ReportStatus::FINALIZED && empty($report->archived_data)) {
            $this->captureSnapshot->execute($report);
        }
    }
}
