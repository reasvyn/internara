<?php

declare(strict_types=1);

namespace App\Assignment\Submission\Events;

use App\Assignment\Submission\Models\Submission;
use App\Core\Events\BaseEvent;

final class SubmissionRevisionRequested extends BaseEvent
{
    public function __construct(public Submission $submission) {}

    public function eventName(): string
    {
        return 'submission.revision_requested';
    }
}
