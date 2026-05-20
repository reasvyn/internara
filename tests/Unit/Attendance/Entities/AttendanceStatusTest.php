<?php

declare(strict_types=1);

use App\Domain\Attendance\Entities\AttendanceStatus as AttendanceStatusEntity;
use App\Domain\Attendance\Enums\AttendanceStatus;
use App\Domain\Core\Entities\BaseEntity;
use Carbon\Carbon;

describe('AttendanceStatus entity', function () {
    it('detects clock out presence', function () {
        $entity = new AttendanceStatusEntity(
            status: AttendanceStatus::PRESENT,
            clockOut: Carbon::now(),
        );

        expect($entity->hasClockOut())->toBeTrue();
    });

    it('detects missing clock out', function () {
        $entity = new AttendanceStatusEntity(
            status: AttendanceStatus::PRESENT,
            clockOut: null,
        );

        expect($entity->hasClockOut())->toBeFalse();
    });

    it('detects excused status', function () {
        $entity = new AttendanceStatusEntity(
            status: AttendanceStatus::SICK,
            clockOut: null,
        );

        expect($entity->isExcused())->toBeTrue();
    });

    it('detects not excused', function () {
        $entity = new AttendanceStatusEntity(
            status: AttendanceStatus::PRESENT,
            clockOut: null,
        );

        expect($entity->isExcused())->toBeFalse();
    });

    it('handles null status', function () {
        $entity = new AttendanceStatusEntity(status: null, clockOut: null);

        expect($entity->isExcused())->toBeFalse();
    });

    it('is final readonly', function () {
        $ref = new ReflectionClass(AttendanceStatusEntity::class);

        expect($ref->isFinal())->toBeTrue()
            ->and($ref->isReadOnly())->toBeTrue();
    });

    it('extends BaseEntity', function () {
        expect(AttendanceStatusEntity::class)->toExtend(BaseEntity::class);
    });
});
