<?php

declare(strict_types=1);

use App\Domain\Assignment\Entities\SubmissionState;
use App\Domain\Assignment\Enums\SubmissionStatus;
use App\Domain\Core\Entities\BaseEntity;

describe('SubmissionState entity', function () {
    it('detects verified', function () {
        $entity = new SubmissionState(status: SubmissionStatus::VERIFIED);

        expect($entity->isVerified())->toBeTrue();
    });

    it('detects not verified', function () {
        $entity = new SubmissionState(status: SubmissionStatus::DRAFT);

        expect($entity->isVerified())->toBeFalse();
    });

    it('can be edited in draft', function () {
        $entity = new SubmissionState(status: SubmissionStatus::DRAFT);

        expect($entity->canBeEdited())->toBeTrue();
    });

    it('cannot be edited when submitted', function () {
        $entity = new SubmissionState(status: SubmissionStatus::SUBMITTED);

        expect($entity->canBeEdited())->toBeFalse();
    });

    it('is final readonly', function () {
        $ref = new ReflectionClass(SubmissionState::class);

        expect($ref->isFinal())->toBeTrue()
            ->and($ref->isReadOnly())->toBeTrue();
    });

    it('extends BaseEntity', function () {
        expect(SubmissionState::class)->toExtend(BaseEntity::class);
    });
});
