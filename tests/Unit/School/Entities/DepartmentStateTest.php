<?php

declare(strict_types=1);

use App\Domain\Core\Entities\BaseEntity;
use App\Domain\School\Entities\DepartmentState;

describe('DepartmentState entity', function () {
    it('can be deleted when no profiles', function () {
        $entity = new DepartmentState(profileCount: 0, hasProfiles: false);

        expect($entity->canBeDeleted())->toBeTrue();
    });

    it('cannot be deleted when has profiles', function () {
        $entity = new DepartmentState(profileCount: 5, hasProfiles: true);

        expect($entity->canBeDeleted())->toBeFalse();
    });

    it('is final readonly', function () {
        $ref = new ReflectionClass(DepartmentState::class);

        expect($ref->isFinal())->toBeTrue()
            ->and($ref->isReadOnly())->toBeTrue();
    });

    it('extends BaseEntity', function () {
        expect(DepartmentState::class)->toExtend(BaseEntity::class);
    });
});
