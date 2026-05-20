<?php

declare(strict_types=1);

use App\Domain\Attendance\Enums\AttendanceStatus;
use App\Domain\Core\Contracts\LabelEnum;
use App\Domain\Core\Contracts\StatusEnum;

describe('AttendanceStatus enum', function () {
    it('implements LabelEnum', function () {
        expect(AttendanceStatus::class)->toImplement(LabelEnum::class);
    });

    it('implements StatusEnum', function () {
        expect(AttendanceStatus::class)->toImplement(StatusEnum::class);
    });

    it('has labels', function () {
        expect(AttendanceStatus::PRESENT->label())->toBe('Present')
            ->and(AttendanceStatus::LATE->label())->toBe('Late')
            ->and(AttendanceStatus::SICK->label())->toBe('Sick');
    });

    it('detects on-time', function () {
        expect(AttendanceStatus::PRESENT->isOnTime())->toBeTrue()
            ->and(AttendanceStatus::LATE->isOnTime())->toBeFalse();
    });

    it('detects excused', function () {
        expect(AttendanceStatus::PERMISSION->isExcused())->toBeTrue()
            ->and(AttendanceStatus::SICK->isExcused())->toBeTrue()
            ->and(AttendanceStatus::PRESENT->isExcused())->toBeFalse();
    });
});
