<?php

declare(strict_types=1);

namespace App\Reports\Report\Observers;

use App\Reports\Report\Enums\ReportStatus;
use App\Reports\Report\Models\Report;

class ReportObserver
{
    public function saved(Report $report): void
    {
        if ($report->status === ReportStatus::FINALIZED) {
            $report->captureSnapshot();

            if ($report->isDirty()) {
                $report->saveQuietly();
            }
        }
    }
}
