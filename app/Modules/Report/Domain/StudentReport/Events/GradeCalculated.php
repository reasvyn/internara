<?php

declare(strict_types=1);

namespace App\Modules\Report\Domain\StudentReport\Events;

use App\Modules\Core\Events\BaseEvent;
use App\Modules\Report\Domain\StudentReport\Models\StudentReport;

final class GradeCalculated extends BaseEvent
{
    public function __construct(public StudentReport $report) {}

    public function eventName(): string
    {
        return 'report.grade_calculated';
    }
}
