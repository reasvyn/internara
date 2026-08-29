<?php

declare(strict_types=1);

namespace App\Modules\Reports\Domain\StudentReport\Observers;

use App\Modules\Reports\Domain\StudentReport\Actions\CaptureReportSnapshotAction;
use App\Modules\Reports\Domain\StudentReport\Enums\StudentReportStatus;

class StudentReportObserver
{
    public function __construct(
        private readonly CaptureReportSnapshotAction $captureSnapshot,
    ) {}

    public function saved(Report $report): void
    {
        if ($report->status === StudentReportStatus::FINALIZED && empty($report->archived_data)) {
            $this->captureSnapshot->execute($report);
        }
    }
}
