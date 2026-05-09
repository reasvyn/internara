<?php

declare(strict_types=1);

use App\Entities\Attendance\AttendanceStatus;
use App\Enums\Attendance\AttendanceStatus as AttendanceStatusEnum;
use Carbon\Carbon;

it('detects excused attendance', function () {
    $entity = new AttendanceStatus(AttendanceStatusEnum::PERMISSION, null);

    expect($entity->isExcused())->toBeTrue();
});

it('detects not excused attendance', function () {
    $entity = new AttendanceStatus(AttendanceStatusEnum::PRESENT, null);

    expect($entity->isExcused())->toBeFalse();
});

it('detects clock out', function () {
    $entity = new AttendanceStatus(null, Carbon::now());

    expect($entity->hasClockOut())->toBeTrue();
});

it('detects no clock out', function () {
    $entity = new AttendanceStatus(null, null);

    expect($entity->hasClockOut())->toBeFalse();
});
