<?php

declare(strict_types=1);

use App\Entities\Submission\SubmissionState;
use App\Enums\Assignment\SubmissionStatus;

it('can be edited when draft', function () {
    $entity = new SubmissionState(SubmissionStatus::DRAFT);

    expect($entity->canBeEdited())->toBeTrue();
});

it('can be edited when revision required', function () {
    $entity = new SubmissionState(SubmissionStatus::REVISION_REQUIRED);

    expect($entity->canBeEdited())->toBeTrue();
});

it('cannot be edited when submitted', function () {
    $entity = new SubmissionState(SubmissionStatus::SUBMITTED);

    expect($entity->canBeEdited())->toBeFalse();
});

it('cannot be edited when verified', function () {
    $entity = new SubmissionState(SubmissionStatus::VERIFIED);

    expect($entity->canBeEdited())->toBeFalse();
});

it('detects verified submission', function () {
    $entity = new SubmissionState(SubmissionStatus::VERIFIED);

    expect($entity->isVerified())->toBeTrue();
});

it('detects not verified submission', function () {
    $entity = new SubmissionState(SubmissionStatus::DRAFT);

    expect($entity->isVerified())->toBeFalse();
});
