<?php

declare(strict_types=1);

use App\Guidance\MonitoringVisit\Entities\VisitState;
use Carbon\Carbon;

test('visit state can be edited when not verified', function () {
    $unverified = new VisitState(false, null);
    expect($unverified->canBeEdited())->toBeTrue();

    $verified = new VisitState(true, null);
    expect($verified->canBeEdited())->toBeFalse();
});

test('visit state can be deleted when not verified', function () {
    $unverified = new VisitState(false, null);
    expect($unverified->canBeDeleted())->toBeTrue();

    $verified = new VisitState(true, null);
    expect($verified->canBeDeleted())->toBeFalse();
});

test('visit state detects recent visits within 7 days', function () {
    $recent = new VisitState(false, Carbon::now()->subDays(2));
    expect($recent->isRecent())->toBeTrue();

    $old = new VisitState(false, Carbon::now()->subDays(10));
    expect($old->isRecent())->toBeFalse();
});

test('visit state is not recent when visit date is null', function () {
    $state = new VisitState(false, null);
    expect($state->isRecent())->toBeFalse();
});

test('visit state is recent with custom now reference', function () {
    $visitDate = Carbon::parse('2025-06-01');
    $now = Carbon::parse('2025-06-05');
    $state = new VisitState(false, $visitDate);
    expect($state->isRecent($now))->toBeTrue();

    $laterNow = Carbon::parse('2025-06-20');
    expect($state->isRecent($laterNow))->toBeFalse();
});
