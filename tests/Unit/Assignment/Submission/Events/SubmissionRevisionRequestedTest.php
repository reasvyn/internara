<?php

declare(strict_types=1);

use App\Assignment\Submission\Events\SubmissionRevisionRequested;
use App\Assignment\Submission\Models\Submission;

test('submission revision requested has submission payload', function () {
    $submission = new class extends Submission {};
    $submission->forceFill(['id' => 's-1']);

    $event = new SubmissionRevisionRequested($submission);

    expect($event->submission->id)->toBe('s-1');
    expect($event->eventName())->toBe('submission.revision_requested');
});
