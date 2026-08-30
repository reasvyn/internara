<?php

declare(strict_types=1);

namespace App\Modules\Reports\Domain\StudentReport\Listeners;

use App\Modules\Core\Services\SmartLogger;
use App\Modules\Reports\Domain\StudentReport\Events\StudentReportFinalized;

class LogStudentReportFinalized
{
    public function handle(StudentReportFinalized $event): void
    {
        SmartLogger::info('report_finalized')
            ->module('Reports')
            ->withPayload([
                'report_id' => $event->studentReport->id,
                'registration_id' => $event->studentReport->registration_id,
                'finalized_by' => $event->studentReport->finalized_by,
                'final_score' => $event->studentReport->final_score,
                'grade_letter' => $event->studentReport->grade_letter,
            ])
            ->withPiiMasking()
            ->systemOnly()
            ->save();
    }
}
