<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Events;

use App\Modules\Assessment\Models\Assessment;
use App\Modules\Core\Events\BaseEvent;

final class AssessmentFinalized extends BaseEvent
{
    public function __construct(public Assessment $assessment) {}

    public function eventName(): string
    {
        return 'assessment.finalized';
    }
}
