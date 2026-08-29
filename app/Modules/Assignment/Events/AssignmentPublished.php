<?php

declare(strict_types=1);

namespace App\Modules\Assignment\Events;

use App\Modules\Assignment\Models\Assignment;
use App\Modules\Core\Events\BaseEvent;

final class AssignmentPublished extends BaseEvent
{
    public function __construct(public Assignment $assignment) {}

    public function eventName(): string
    {
        return 'assignment.published';
    }
}
