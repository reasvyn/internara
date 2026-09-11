<?php

declare(strict_types=1);

use App\Modules\Assignment\Enums\AssignmentStatus;
use App\Modules\Journals\Domain\Attendance\Enums\AttendanceStatus;

describe('1KSWL: AttendanceStatus enum', function (): void {
    test('1KSWL-FR-DAILY-009: cases carry the specified backing values', function (): void {
        expect(AttendanceStatus::PRESENT->value)->toBe('present');
        expect(AttendanceStatus::from('present'))->toBe(AttendanceStatus::PRESENT);
        expect(AttendanceStatus::LATE->value)->toBe('late');
        expect(AttendanceStatus::from('late'))->toBe(AttendanceStatus::LATE);
        expect(AttendanceStatus::EARLY_OUT->value)->toBe('early_out');
        expect(AttendanceStatus::from('early_out'))->toBe(AttendanceStatus::EARLY_OUT);
        expect(AttendanceStatus::ABSENT->value)->toBe('absent');
        expect(AttendanceStatus::from('absent'))->toBe(AttendanceStatus::ABSENT);
        expect(AttendanceStatus::PERMISSION->value)->toBe('permission');
        expect(AttendanceStatus::from('permission'))->toBe(AttendanceStatus::PERMISSION);
        expect(AttendanceStatus::SICK->value)->toBe('sick');
        expect(AttendanceStatus::from('sick'))->toBe(AttendanceStatus::SICK);
        expect(AttendanceStatus::cases())->toHaveCount(6);
        expect(AttendanceStatus::tryFrom('no-such-value'))->toBeNull();
    });
    test('1KSWL-FR-DAILY-009: labels resolve in English', function (): void {
        app()->setLocale('en');

        expect(AttendanceStatus::PRESENT->label())->toBe('Present');
        expect(AttendanceStatus::LATE->label())->toBe('Late');
        expect(AttendanceStatus::EARLY_OUT->label())->toBe('Early Out');
        expect(AttendanceStatus::ABSENT->label())->toBe('Absent');
        expect(AttendanceStatus::PERMISSION->label())->toBe('Permission');
        expect(AttendanceStatus::SICK->label())->toBe('Sick');
    });

    test('1KSWL-FR-DAILY-009: labels resolve in Indonesian', function (): void {
        app()->setLocale('id');

        expect(AttendanceStatus::PRESENT->label())->toBe('Hadir');
        expect(AttendanceStatus::LATE->label())->toBe('Terlambat');
        expect(AttendanceStatus::EARLY_OUT->label())->toBe('Pulang Awal');
        expect(AttendanceStatus::ABSENT->label())->toBe('Tidak Hadir');
        expect(AttendanceStatus::PERMISSION->label())->toBe('Izin');
        expect(AttendanceStatus::SICK->label())->toBe('Sakit');
    });
    test('1KSWL-FR-DAILY-009: only present counts as on time', function (): void {
        expect(AttendanceStatus::PRESENT->isOnTime())->toBeTrue();
        expect(AttendanceStatus::LATE->isOnTime())->toBeFalse();
        expect(AttendanceStatus::ABSENT->isOnTime())->toBeFalse();
        expect(AttendanceStatus::PERMISSION->isOnTime())->toBeFalse();
    });

    test('1KSWL-FR-DAILY-009: permission and sick count as excused, the rest do not', function (): void {
        expect(AttendanceStatus::PERMISSION->isExcused())->toBeTrue();
        expect(AttendanceStatus::SICK->isExcused())->toBeTrue();
        expect(AttendanceStatus::PRESENT->isExcused())->toBeFalse();
        expect(AttendanceStatus::LATE->isExcused())->toBeFalse();
        expect(AttendanceStatus::ABSENT->isExcused())->toBeFalse();
        expect(AttendanceStatus::EARLY_OUT->isExcused())->toBeFalse();
    });

    test('1KSWL-FR-DAILY-009: attendance records are terminal with no onward transitions', function (): void {
        foreach (AttendanceStatus::cases() as $status) {
            expect($status->isTerminal())->toBeTrue();
            expect($status->validTransitions())->toBe([]);
            expect($status->canTransitionTo(AttendanceStatus::PRESENT))->toBeFalse();
        }
        expect(AttendanceStatus::PRESENT->canTransitionTo(AssignmentStatus::PUBLISHED))->toBeFalse();
    });
});
