<?php

declare(strict_types=1);

namespace App\Assignment\Events;

use App\Assignment\Models\Assignment;
use App\Core\Events\BaseEvent;

final class AssignmentPublished extends BaseEvent
{
    public function __construct(public Assignment $assignment) {}

    public function eventName(): string
    {
        return 'assignment.published';
    }
}
