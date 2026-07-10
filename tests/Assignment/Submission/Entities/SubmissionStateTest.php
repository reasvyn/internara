<?php

declare(strict_types=1);

use App\Assignment\Submission\Entities\SubmissionState;
use App\Assignment\Submission\Enums\SubmissionStatus;

test('submission state can be edited for draft and revision required', function () {
    expect((new SubmissionState(SubmissionStatus::DRAFT))->canBeEdited())->toBeTrue();
    expect((new SubmissionState(SubmissionStatus::REVISION_REQUIRED))->canBeEdited())->toBeTrue();
    expect((new SubmissionState(SubmissionStatus::SUBMITTED))->canBeEdited())->toBeFalse();
    expect((new SubmissionState(SubmissionStatus::VERIFIED))->canBeEdited())->toBeFalse();
    expect((new SubmissionState(SubmissionStatus::GRADED))->canBeEdited())->toBeFalse();
});

test('submission state detects verified', function () {
    expect((new SubmissionState(SubmissionStatus::VERIFIED))->isVerified())->toBeTrue();
    expect((new SubmissionState(SubmissionStatus::GRADED))->isVerified())->toBeFalse();
    expect((new SubmissionState(SubmissionStatus::DRAFT))->isVerified())->toBeFalse();
});
