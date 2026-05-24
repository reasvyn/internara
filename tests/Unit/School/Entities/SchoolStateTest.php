<?php

declare(strict_types=1);

use App\Domain\Core\Entities\BaseEntity;
use App\Domain\School\Entities\SchoolState;

describe('SchoolState entity', function () {
    it('blocks creation when a record exists', function () {
        $entity = new SchoolState(existsCount: 1);

        expect($entity->canBeCreated())->toBeFalse();
    });

    it('allows creation when no record exists', function () {
        $entity = new SchoolState(existsCount: 0);

        expect($entity->canBeCreated())->toBeTrue();
    });

    it('is final readonly', function () {
        $ref = new ReflectionClass(SchoolState::class);

        expect($ref->isFinal())->toBeTrue()
            ->and($ref->isReadOnly())->toBeTrue();
    });

    it('extends BaseEntity', function () {
        expect(SchoolState::class)->toExtend(BaseEntity::class);
    });
});
