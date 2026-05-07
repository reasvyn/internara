<?php

declare(strict_types=1);

use App\Entities\Schedule\ScheduleStatus;
use Carbon\Carbon;

it('detects ongoing schedule', function () {
    $entity = new ScheduleStatus(Carbon::yesterday(), Carbon::tomorrow());

    expect($entity->isOngoing())->toBeTrue();
});

it('detects ongoing schedule with no end', function () {
    $entity = new ScheduleStatus(Carbon::yesterday(), null);

    expect($entity->isOngoing())->toBeTrue();
});

it('detects not ongoing when in the past', function () {
    $entity = new ScheduleStatus(Carbon::parse('-10 days'), Carbon::parse('-5 days'));

    expect($entity->isOngoing())->toBeFalse();
});

it('detects upcoming schedule', function () {
    $entity = new ScheduleStatus(Carbon::tomorrow(), Carbon::parse('+5 days'));

    expect($entity->isUpcoming())->toBeTrue();
});

it('detects not upcoming when already started', function () {
    $entity = new ScheduleStatus(Carbon::yesterday(), Carbon::tomorrow());

    expect($entity->isUpcoming())->toBeFalse();
});
