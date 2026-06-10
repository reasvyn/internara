<?php

declare(strict_types=1);

use App\Journals\Schedule\Entities\ScheduleStatus;
use Carbon\Carbon;

test('schedule status detects ongoing event', function () {
    $now = new Carbon('2026-06-10 12:00:00');
    $ongoing = new ScheduleStatus(new Carbon('2026-06-10 10:00:00'), new Carbon('2026-06-10 14:00:00'));

    expect($ongoing->isOngoing($now))->toBeTrue();
    expect($ongoing->isUpcoming($now))->toBeFalse();
});

test('schedule status detects upcoming event', function () {
    $now = new Carbon('2026-06-10 12:00:00');
    $upcoming = new ScheduleStatus(new Carbon('2026-06-11 10:00:00'), null);

    expect($upcoming->isUpcoming($now))->toBeTrue();
    expect($upcoming->isOngoing($now))->toBeFalse();
});

test('schedule status with no end is still ongoing', function () {
    $now = new Carbon('2026-06-10 12:00:00');
    $noEnd = new ScheduleStatus(new Carbon('2026-06-10 10:00:00'), null);

    expect($noEnd->isOngoing($now))->toBeTrue();
});

test('schedule status past event', function () {
    $now = new Carbon('2026-06-10 12:00:00');
    $past = new ScheduleStatus(new Carbon('2026-06-09 10:00:00'), new Carbon('2026-06-09 11:00:00'));

    expect($past->isOngoing($now))->toBeFalse();
    expect($past->isUpcoming($now))->toBeFalse();
});
