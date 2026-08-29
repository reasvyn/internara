<?php

declare(strict_types=1);

namespace App\Modules\Reports\Domain\StudentReport\Listeners;

use App\Modules\Core\Services\SmartLogger;
use App\Modules\Reports\Domain\StudentReport\Events\ReportFinalized;

class LogReportFinalized
{
    public function handle(ReportFinalized $event): void
    {
        SmartLogger::info('report_finalized')
            ->module('Reports')
            ->withPayload([
                'report_id' => $event->report->id,
                'registration_id' => $event->report->registration_id,
                'finalized_by' => $event->report->finalized_by,
                'final_score' => $event->report->final_score,
                'grade_letter' => $event->report->grade_letter,
            ])
            ->withPiiMasking()
            ->systemOnly()
            ->save();
    }
}
