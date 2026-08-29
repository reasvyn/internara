<?php

declare(strict_types=1);

namespace App\Modules\Reports\Domain\Report\Events;

use App\Modules\Core\Events\BaseEvent;
use App\Modules\Reports\Domain\Report\Models\Report;

final class ReportFinalized extends BaseEvent
{
    public function __construct(public Report $report) {}

    public function eventName(): string
    {
        return 'report.finalized';
    }
}
