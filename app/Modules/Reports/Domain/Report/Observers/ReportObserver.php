<?php

declare(strict_types=1);

namespace App\Modules\Reports\Domain\Report\Observers;

use App\Modules\Reports\Domain\Report\Actions\CaptureReportSnapshotAction;
use App\Modules\Reports\Domain\Report\Enums\ReportStatus;
use App\Modules\Reports\Domain\Report\Models\Report;

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
