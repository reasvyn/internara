<?php

declare(strict_types=1);

use App\Journals\Attendance\Events\AttendanceClockIn;
use App\Journals\Attendance\Events\AttendanceClockOut;
use App\Journals\Attendance\Models\Attendance;

function makeAttendance(string $id): Attendance
{
    $model = new class extends Attendance {};
    $model->forceFill(['id' => $id]);

    return $model;
}

test('attendance clock in event name and payload', function () {
    $event = new AttendanceClockIn(makeAttendance('a-1'));

    expect($event->attendance->id)->toBe('a-1');
    expect($event->eventName())->toBe('attendance.clock_in');
    expect($event->toPayload())->toHaveKey('attendance_id');
});

test('attendance clock out event name and payload', function () {
    $event = new AttendanceClockOut(makeAttendance('a-2'));

    expect($event->attendance->id)->toBe('a-2');
    expect($event->eventName())->toBe('attendance.clock_out');
    expect($event->toPayload())->toHaveKey('attendance_id');
});
