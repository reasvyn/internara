<?php

declare(strict_types=1);

namespace App\Assessment\Listeners;

use App\Assessment\Events\AssessmentFinalized;
use App\Core\Support\SmartLogger;

class LogAssessmentFinalized
{
    public function handle(AssessmentFinalized $event): void
    {
        SmartLogger::info('Assessment finalized')
            ->about($event->assessment)
            ->module('assessment')
            ->systemOnly()
            ->save();
    }
}
