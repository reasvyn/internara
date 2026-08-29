<?php

declare(strict_types=1);

namespace App\Modules\Reports\Domain\StudentReport\Events;

use App\Modules\Core\Events\BaseEvent;

final class StudentReportFinalized extends BaseEvent
{
    public function __construct(public Report $report) {}

    public function eventName(): string
    {
        return 'report.finalized';
    }
}
