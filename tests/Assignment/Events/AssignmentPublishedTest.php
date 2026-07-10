<?php

declare(strict_types=1);

use App\Assignment\Events\AssignmentPublished;
use App\Assignment\Models\Assignment;

test('assignment published event name and payload', function () {
    $assignment = new class extends Assignment {};
    $assignment->forceFill(['id' => 'as-1']);

    $event = new AssignmentPublished($assignment);

    expect($event->assignment->id)->toBe('as-1');
    expect($event->eventName())->toBe('assignment.published');
    expect($event->toPayload())->toHaveKey('assignment_id');
});
