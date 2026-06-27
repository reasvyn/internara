<?php

declare(strict_types=1);

namespace App\Assessment\Events;

use App\Assessment\Models\Assessment;
use App\Core\Events\BaseEvent;

final class AssessmentFinalized extends BaseEvent
{
    public function __construct(public Assessment $assessment) {}

    public function eventName(): string
    {
        return 'assessment.finalized';
    }
}
