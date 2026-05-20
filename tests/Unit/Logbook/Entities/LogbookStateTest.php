<?php

declare(strict_types=1);

use App\Domain\Core\Entities\BaseEntity;
use App\Domain\Logbook\Entities\LogbookState;
use App\Domain\Logbook\Enums\LogbookStatus;

describe('LogbookState entity', function () {
    it('detects verified status', function () {
        $entity = new LogbookState(status: LogbookStatus::VERIFIED);

        expect($entity->isVerified())->toBeTrue();
    });

    it('detects not verified', function () {
        $entity = new LogbookState(status: LogbookStatus::DRAFT);

        expect($entity->isVerified())->toBeFalse();
    });

    it('can be edited in draft', function () {
        $entity = new LogbookState(status: LogbookStatus::DRAFT);

        expect($entity->canBeEdited())->toBeTrue();
    });

    it('can be edited in revision required', function () {
        $entity = new LogbookState(status: LogbookStatus::REVISION_REQUIRED);

        expect($entity->canBeEdited())->toBeTrue();
    });

    it('cannot be edited when submitted', function () {
        $entity = new LogbookState(status: LogbookStatus::SUBMITTED);

        expect($entity->canBeEdited())->toBeFalse();
    });

    it('cannot be edited when verified', function () {
        $entity = new LogbookState(status: LogbookStatus::VERIFIED);

        expect($entity->canBeEdited())->toBeFalse();
    });

    it('is final readonly', function () {
        $ref = new ReflectionClass(LogbookState::class);

        expect($ref->isFinal())->toBeTrue()
            ->and($ref->isReadOnly())->toBeTrue();
    });

    it('extends BaseEntity', function () {
        expect(LogbookState::class)->toExtend(BaseEntity::class);
    });
});
