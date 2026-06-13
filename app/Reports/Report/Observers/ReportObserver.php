<?php

declare(strict_types=1);

namespace App\Reports\Report\Observers;

use App\Reports\Report\Models\Report;

class ReportObserver
{
    public function saving(Report $report): void
    {
        $report->captureSnapshot();
    }
}
