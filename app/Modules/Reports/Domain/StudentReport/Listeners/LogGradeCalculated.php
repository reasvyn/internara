<?php

declare(strict_types=1);

namespace App\Modules\Reports\Domain\StudentReport\Listeners;

use App\Modules\Core\Services\SmartLogger;
use App\Modules\Reports\Domain\StudentReport\Events\GradeCalculated;

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
