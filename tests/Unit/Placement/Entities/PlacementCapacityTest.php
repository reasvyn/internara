<?php

declare(strict_types=1);

use App\Domain\Core\Entities\BaseEntity;
use App\Domain\Placement\Entities\PlacementCapacity;

describe('PlacementCapacity entity', function () {
    it('detects full capacity', function () {
        $entity = new PlacementCapacity(quota: 5, filledQuota: 5);

        expect($entity->isFull())->toBeTrue();
    });

    it('detects available capacity', function () {
        $entity = new PlacementCapacity(quota: 5, filledQuota: 3);

        expect($entity->isFull())->toBeFalse();
    });

    it('calculates available slots', function () {
        $entity = new PlacementCapacity(quota: 10, filledQuota: 4);

        expect($entity->availableSlots())->toBe(6);
    });

    it('returns zero available slots when full', function () {
        $entity = new PlacementCapacity(quota: 3, filledQuota: 3);

        expect($entity->availableSlots())->toBe(0);
    });

    it('detects has available slots', function () {
        $entity = new PlacementCapacity(quota: 5, filledQuota: 2);

        expect($entity->hasAvailableSlots())->toBeTrue();
    });

    it('detects no available slots', function () {
        $entity = new PlacementCapacity(quota: 5, filledQuota: 5);

        expect($entity->hasAvailableSlots())->toBeFalse();
    });

    it('is final readonly', function () {
        $ref = new ReflectionClass(PlacementCapacity::class);

        expect($ref->isFinal())->toBeTrue()
            ->and($ref->isReadOnly())->toBeTrue();
    });

    it('extends BaseEntity', function () {
        expect(PlacementCapacity::class)->toExtend(BaseEntity::class);
    });
});
