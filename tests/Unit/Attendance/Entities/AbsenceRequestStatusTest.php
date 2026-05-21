<?php

declare(strict_types=1);

use App\Domain\Attendance\Entities\AbsenceRequestStatus as AbsenceRequestStatusEntity;
use App\Domain\Attendance\Enums\AbsenceRequestStatus;
use App\Domain\Core\Entities\BaseEntity;

describe('AbsenceRequestStatus entity', function () {
    it('detects pending', function () {
        $entity = new AbsenceRequestStatusEntity(status: AbsenceRequestStatus::PENDING);

        expect($entity->isPending())->toBeTrue()
            ->and($entity->isProcessed())->toBeFalse();
    });

    it('detects processed', function () {
        $entity = new AbsenceRequestStatusEntity(status: AbsenceRequestStatus::APPROVED);

        expect($entity->isProcessed())->toBeTrue()
            ->and($entity->isPending())->toBeFalse();
    });

    it('handles null status', function () {
        $entity = new AbsenceRequestStatusEntity(status: null);

        expect($entity->isProcessed())->toBeFalse();
    });

    it('is final readonly', function () {
        $ref = new ReflectionClass(AbsenceRequestStatusEntity::class);

        expect($ref->isFinal())->toBeTrue()
            ->and($ref->isReadOnly())->toBeTrue();
    });

    it('extends BaseEntity', function () {
        expect(AbsenceRequestStatusEntity::class)->toExtend(BaseEntity::class);
    });
});
