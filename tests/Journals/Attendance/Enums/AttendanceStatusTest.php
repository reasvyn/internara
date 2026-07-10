<?php

declare(strict_types=1);

use App\Journals\Attendance\Enums\AttendanceStatus;

test('attendance status has all cases', function () {
    expect(AttendanceStatus::cases())->toHaveCount(6);
    expect(AttendanceStatus::PRESENT->value)->toBe('present');
    expect(AttendanceStatus::LATE->value)->toBe('late');
    expect(AttendanceStatus::EARLY_OUT->value)->toBe('early_out');
    expect(AttendanceStatus::ABSENT->value)->toBe('absent');
    expect(AttendanceStatus::PERMISSION->value)->toBe('permission');
    expect(AttendanceStatus::SICK->value)->toBe('sick');
});

test('attendance status labels are non-empty', function () {
    foreach (AttendanceStatus::cases() as $s) {
        expect($s->label())->toBeString()->not->toBeEmpty();
    }
});

test('only present is on time', function () {
    expect(AttendanceStatus::PRESENT->isOnTime())->toBeTrue();
    expect(AttendanceStatus::LATE->isOnTime())->toBeFalse();
    expect(AttendanceStatus::EARLY_OUT->isOnTime())->toBeFalse();
    expect(AttendanceStatus::ABSENT->isOnTime())->toBeFalse();
});

test('permission and sick are excused', function () {
    expect(AttendanceStatus::PERMISSION->isExcused())->toBeTrue();
    expect(AttendanceStatus::SICK->isExcused())->toBeTrue();
    expect(AttendanceStatus::PRESENT->isExcused())->toBeFalse();
    expect(AttendanceStatus::LATE->isExcused())->toBeFalse();
    expect(AttendanceStatus::ABSENT->isExcused())->toBeFalse();
});

test('all attendance statuses are terminal', function () {
    foreach (AttendanceStatus::cases() as $s) {
        expect($s->isTerminal())->toBeTrue();
    }
});

test('no valid transitions exist', function () {
    foreach (AttendanceStatus::cases() as $s) {
        expect($s->validTransitions())->toBe([]);
        expect($s->canTransitionTo(AttendanceStatus::PRESENT))->toBeFalse();
    }
});
