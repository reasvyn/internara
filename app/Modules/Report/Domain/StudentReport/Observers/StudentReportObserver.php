<?php

declare(strict_types=1);

namespace App\Modules\Report\Domain\StudentReport\Observers;

use App\Modules\Report\Domain\StudentReport\Actions\CaptureStudentReportSnapshotAction;
use App\Modules\Report\Domain\StudentReport\Enums\StudentReportStatus;
use App\Modules\Report\Domain\StudentReport\Models\StudentReport;

class StudentReportObserver
{
    public function __construct(
        private readonly CaptureStudentReportSnapshotAction $captureSnapshot,
    ) {}

    public function saved(StudentReport $report): void
    {
        if ($report->status === StudentReportStatus::FINALIZED && empty($report->archived_data)) {
            $this->captureSnapshot->execute($report);
        }
    }
}
