<?php

declare(strict_types=1);

use App\Assessment\Events\AssessmentFinalized;
use App\Assessment\Models\Assessment;

test('dispatches with assessment payload', function () {
    $assessment = new class extends Assessment {};
    $assessment->forceFill(['id' => 'test-1']);

    $event = new AssessmentFinalized($assessment);

    expect($event->assessment->id)->toBe('test-1');
    expect($event->eventName())->toBe('assessment.finalized');
    expect($event->toPayload())->toHaveKey('assessment_id');
});
