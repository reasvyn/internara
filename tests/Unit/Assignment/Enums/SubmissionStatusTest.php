<?php

declare(strict_types=1);

use App\Domain\Assignment\Enums\SubmissionStatus;
use App\Domain\Core\Contracts\LabelEnum;
use App\Domain\Core\Contracts\StatusEnum;

describe('SubmissionStatus enum', function () {
    it('implements LabelEnum', function () {
        expect(SubmissionStatus::class)->toImplement(LabelEnum::class);
    });

    it('implements StatusEnum', function () {
        expect(SubmissionStatus::class)->toImplement(StatusEnum::class);
    });

    it('has labels', function () {
        expect(SubmissionStatus::DRAFT->label())->toBe('Draft')
            ->and(SubmissionStatus::SUBMITTED->label())->toBe('Submitted')
            ->and(SubmissionStatus::GRADED->label())->toBe('Graded');
    });

    it('detects finalized', function () {
        expect(SubmissionStatus::GRADED->isFinalized())->toBeTrue()
            ->and(SubmissionStatus::DRAFT->isFinalized())->toBeFalse();
    });

    it('detects requires action', function () {
        expect(SubmissionStatus::SUBMITTED->requiresAction())->toBeTrue()
            ->and(SubmissionStatus::DRAFT->requiresAction())->toBeFalse();
    });

    it('validates transitions', function () {
        expect(SubmissionStatus::DRAFT->canTransitionTo(SubmissionStatus::SUBMITTED))->toBeTrue()
            ->and(SubmissionStatus::SUBMITTED->canTransitionTo(SubmissionStatus::GRADED))->toBeTrue()
            ->and(SubmissionStatus::SUBMITTED->canTransitionTo(SubmissionStatus::REVISION_REQUIRED))->toBeTrue()
            ->and(SubmissionStatus::REVISION_REQUIRED->canTransitionTo(SubmissionStatus::DRAFT))->toBeTrue()
            ->and(SubmissionStatus::GRADED->canTransitionTo(SubmissionStatus::DRAFT))->toBeFalse();
    });
});
