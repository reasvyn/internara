<?php

declare(strict_types=1);

use App\Domain\Core\Entities\BaseEntity;
use App\Domain\Partnership\Entities\CompanyState;

describe('CompanyState entity', function () {
    it('can be deleted when no placements and no partnerships', function () {
        $entity = new CompanyState(placementCount: 0, partnershipCount: 0);

        expect($entity->canBeDeleted())->toBeTrue();
    });

    it('cannot be deleted when has placements', function () {
        $entity = new CompanyState(placementCount: 3, partnershipCount: 0);

        expect($entity->canBeDeleted())->toBeFalse();
    });

    it('cannot be deleted when has partnerships', function () {
        $entity = new CompanyState(placementCount: 0, partnershipCount: 2);

        expect($entity->canBeDeleted())->toBeFalse();
    });

    it('is final readonly', function () {
        $ref = new ReflectionClass(CompanyState::class);

        expect($ref->isFinal())->toBeTrue()
            ->and($ref->isReadOnly())->toBeTrue();
    });

    it('extends BaseEntity', function () {
        expect(CompanyState::class)->toExtend(BaseEntity::class);
    });
});
