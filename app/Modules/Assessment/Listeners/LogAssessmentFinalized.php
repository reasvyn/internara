<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Listeners;

use App\Modules\Assessment\Events\AssessmentFinalized;
use App\Modules\Core\Services\SmartLogger;

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
