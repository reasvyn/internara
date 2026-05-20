<?php

declare(strict_types=1);

use App\Domain\Core\Entities\BaseEntity;
use App\Domain\School\Entities\AcademicYearState;

describe('AcademicYearState entity', function () {
    it('detects active state', function () {
        $entity = new AcademicYearState(isActive: true);

        expect($entity->isActive())->toBeTrue();
    });

    it('detects inactive state', function () {
        $entity = new AcademicYearState(isActive: false);

        expect($entity->isActive())->toBeFalse();
    });

    it('can be activated when inactive', function () {
        $entity = new AcademicYearState(isActive: false);

        expect($entity->canBeActivated())->toBeTrue();
    });

    it('cannot be activated when already active', function () {
        $entity = new AcademicYearState(isActive: true);

        expect($entity->canBeActivated())->toBeFalse();
    });

    it('can be deleted when inactive and no related records', function () {
        $entity = new AcademicYearState(isActive: false, hasRelatedRecords: false);

        expect($entity->canBeDeleted())->toBeTrue();
    });

    it('cannot be deleted when active', function () {
        $entity = new AcademicYearState(isActive: true, hasRelatedRecords: false);

        expect($entity->canBeDeleted())->toBeFalse();
    });

    it('cannot be deleted when has related records', function () {
        $entity = new AcademicYearState(isActive: false, hasRelatedRecords: true);

        expect($entity->canBeDeleted())->toBeFalse();
    });

    it('detects related records', function () {
        $entity = new AcademicYearState(isActive: false, hasRelatedRecords: true);

        expect($entity->hasRelatedRecords())->toBeTrue();
    });

    it('is final readonly', function () {
        $ref = new ReflectionClass(AcademicYearState::class);

        expect($ref->isFinal())->toBeTrue()
            ->and($ref->isReadOnly())->toBeTrue();
    });

    it('extends BaseEntity', function () {
        expect(AcademicYearState::class)->toExtend(BaseEntity::class);
    });
});
