<?php

declare(strict_types=1);

use App\Journals\Attendance\Enums\AttendanceStatus;
use App\Journals\Attendance\Entities\AttendanceStatus as AttendanceStatusEntity;
use Carbon\Carbon;

test('attendance status entity knows clock out', function () {
    $clockedOut = new AttendanceStatusEntity(AttendanceStatus::PRESENT, new Carbon);
    expect($clockedOut->hasClockOut())->toBeTrue();

    $noClockOut = new AttendanceStatusEntity(AttendanceStatus::PRESENT, null);
    expect($noClockOut->hasClockOut())->toBeFalse();
});

test('attendance status entity knows excused', function () {
    $excused = new AttendanceStatusEntity(AttendanceStatus::PERMISSION, null);
    expect($excused->isExcused())->toBeTrue();

    $present = new AttendanceStatusEntity(AttendanceStatus::PRESENT, null);
    expect($present->isExcused())->toBeFalse();
});

test('attendance status entity handles null status', function () {
    $entity = new AttendanceStatusEntity(null, null);

    expect($entity->hasClockOut())->toBeFalse();
    expect($entity->isExcused())->toBeFalse();
});
