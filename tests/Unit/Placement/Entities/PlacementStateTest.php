<?php

declare(strict_types=1);

use App\Domain\Core\Entities\BaseEntity;
use App\Domain\Placement\Entities\PlacementState;

describe('PlacementState entity', function () {
    it('can be deleted when no registrations', function () {
        $entity = new PlacementState(registrationCount: 0);

        expect($entity->canBeDeleted())->toBeTrue();
    });

    it('cannot be deleted when has registrations', function () {
        $entity = new PlacementState(registrationCount: 3);

        expect($entity->canBeDeleted())->toBeFalse();
    });

    it('is final readonly', function () {
        $ref = new ReflectionClass(PlacementState::class);

        expect($ref->isFinal())->toBeTrue()
            ->and($ref->isReadOnly())->toBeTrue();
    });

    it('extends BaseEntity', function () {
        expect(PlacementState::class)->toExtend(BaseEntity::class);
    });
});
