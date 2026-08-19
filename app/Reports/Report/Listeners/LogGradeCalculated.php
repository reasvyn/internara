<?php

declare(strict_types=1);

namespace App\Reports\Report\Listeners;

use App\Core\Services\SmartLogger;
use App\Reports\Report\Events\GradeCalculated;

class LogGradeCalculated
{
    public function handle(GradeCalculated $event): void
    {
        SmartLogger::info('grade_calculated')
            ->module('Reports')
            ->withPayload([
                'report_id' => $event->report->id,
                'registration_id' => $event->report->registration_id,
                'final_score' => $event->report->final_score,
                'grade_letter' => $event->report->grade_letter,
            ])
            ->withPiiMasking()
            ->systemOnly()
            ->save();
    }
}
