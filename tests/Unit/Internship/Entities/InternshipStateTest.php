<?php

declare(strict_types=1);

use App\Domain\Core\Entities\BaseEntity;
use App\Domain\Internship\Entities\InternshipState;

describe('InternshipState entity', function () {
    it('can be deleted when no placements and no registrations', function () {
        $entity = new InternshipState(placementCount: 0, registrationCount: 0);

        expect($entity->canBeDeleted())->toBeTrue();
    });

    it('cannot be deleted when has placements', function () {
        $entity = new InternshipState(placementCount: 3, registrationCount: 0);

        expect($entity->canBeDeleted())->toBeFalse();
    });

    it('cannot be deleted when has registrations', function () {
        $entity = new InternshipState(placementCount: 0, registrationCount: 2);

        expect($entity->canBeDeleted())->toBeFalse();
    });

    it('is final readonly', function () {
        $ref = new ReflectionClass(InternshipState::class);

        expect($ref->isFinal())->toBeTrue()
            ->and($ref->isReadOnly())->toBeTrue();
    });

    it('extends BaseEntity', function () {
        expect(InternshipState::class)->toExtend(BaseEntity::class);
    });
});
