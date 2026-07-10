<?php

declare(strict_types=1);

use App\Journals\Attendance\Entities\AttendanceState;
use App\Journals\Attendance\Enums\AttendanceStatus;
use Carbon\Carbon;

test('attendance state detects clock out', function () {
    $clockedOut = new AttendanceState(AttendanceStatus::PRESENT, Carbon::now());
    expect($clockedOut->hasClockOut())->toBeTrue();

    $stillWorking = new AttendanceState(AttendanceStatus::PRESENT, null);
    expect($stillWorking->hasClockOut())->toBeFalse();
});

test('attendance state detects excused status', function () {
    $permission = new AttendanceState(AttendanceStatus::PERMISSION, null);
    expect($permission->isExcused())->toBeTrue();

    $sick = new AttendanceState(AttendanceStatus::SICK, null);
    expect($sick->isExcused())->toBeTrue();

    $present = new AttendanceState(AttendanceStatus::PRESENT, null);
    expect($present->isExcused())->toBeFalse();

    $late = new AttendanceState(AttendanceStatus::LATE, null);
    expect($late->isExcused())->toBeFalse();

    $earlyOut = new AttendanceState(AttendanceStatus::EARLY_OUT, null);
    expect($earlyOut->isExcused())->toBeFalse();

    $absent = new AttendanceState(AttendanceStatus::ABSENT, null);
    expect($absent->isExcused())->toBeFalse();
});

test('attendance state with null status is not excused', function () {
    $state = new AttendanceState(null, null);
    expect($state->isExcused())->toBeFalse();
});
