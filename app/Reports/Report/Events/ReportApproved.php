<?php

declare(strict_types=1);

namespace App\Reports\Report\Events;

use App\Core\Events\BaseEvent;
use App\Reports\Report\Models\Report;

final class ReportApproved extends BaseEvent
{
    public function __construct(public Report $report) {}

    public function eventName(): string
    {
        return 'report.approved';
    }
}
