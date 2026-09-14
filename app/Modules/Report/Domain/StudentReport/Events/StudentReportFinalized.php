<?php

declare(strict_types=1);

namespace App\Modules\Report\Domain\StudentReport\Events;

use App\Modules\Core\Events\BaseEvent;
use App\Modules\Report\Domain\StudentReport\Models\StudentReport;

final class StudentReportFinalized extends BaseEvent
{
    public function __construct(public StudentReport $studentReport) {}

    public function eventName(): string
    {
        return 'report.finalized';
    }
}
