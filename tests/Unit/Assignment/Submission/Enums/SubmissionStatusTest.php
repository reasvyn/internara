<?php

declare(strict_types=1);

use App\Assignment\Submission\Enums\SubmissionStatus;

test('submission status has all cases', function () {
    expect(SubmissionStatus::cases())->toHaveCount(5);
    expect(SubmissionStatus::DRAFT->value)->toBe('draft');
    expect(SubmissionStatus::SUBMITTED->value)->toBe('submitted');
    expect(SubmissionStatus::VERIFIED->value)->toBe('verified');
    expect(SubmissionStatus::GRADED->value)->toBe('graded');
    expect(SubmissionStatus::REVISION_REQUIRED->value)->toBe('revision_required');
});

test('submission status labels are non-empty', function () {
    foreach (SubmissionStatus::cases() as $s) {
        expect($s->label())->toBeString()->not->toBeEmpty();
    }
});

test('verified and graded are finalized', function () {
    expect(SubmissionStatus::VERIFIED->isFinalized())->toBeTrue();
    expect(SubmissionStatus::GRADED->isFinalized())->toBeTrue();
    expect(SubmissionStatus::DRAFT->isFinalized())->toBeFalse();
    expect(SubmissionStatus::SUBMITTED->isFinalized())->toBeFalse();
});

test('submitted and revision required require action', function () {
    expect(SubmissionStatus::SUBMITTED->requiresAction())->toBeTrue();
    expect(SubmissionStatus::REVISION_REQUIRED->requiresAction())->toBeTrue();
    expect(SubmissionStatus::DRAFT->requiresAction())->toBeFalse();
    expect(SubmissionStatus::VERIFIED->requiresAction())->toBeFalse();
    expect(SubmissionStatus::GRADED->requiresAction())->toBeFalse();
});

test('verified and graded are terminal', function () {
    expect(SubmissionStatus::VERIFIED->isTerminal())->toBeTrue();
    expect(SubmissionStatus::GRADED->isTerminal())->toBeTrue();
    expect(SubmissionStatus::DRAFT->isTerminal())->toBeFalse();
    expect(SubmissionStatus::SUBMITTED->isTerminal())->toBeFalse();
});

test('valid transitions', function () {
    expect(SubmissionStatus::DRAFT->validTransitions())->toContain(SubmissionStatus::SUBMITTED);
    expect(SubmissionStatus::SUBMITTED->validTransitions())->toContain(SubmissionStatus::VERIFIED, SubmissionStatus::GRADED, SubmissionStatus::REVISION_REQUIRED);
    expect(SubmissionStatus::REVISION_REQUIRED->validTransitions())->toContain(SubmissionStatus::DRAFT);
    expect(SubmissionStatus::VERIFIED->validTransitions())->toBe([]);
    expect(SubmissionStatus::GRADED->validTransitions())->toBe([]);
});

test('can transition correctly', function () {
    expect(SubmissionStatus::DRAFT->canTransitionTo(SubmissionStatus::SUBMITTED))->toBeTrue();
    expect(SubmissionStatus::SUBMITTED->canTransitionTo(SubmissionStatus::GRADED))->toBeTrue();
    expect(SubmissionStatus::SUBMITTED->canTransitionTo(SubmissionStatus::REVISION_REQUIRED))->toBeTrue();
    expect(SubmissionStatus::REVISION_REQUIRED->canTransitionTo(SubmissionStatus::DRAFT))->toBeTrue();
    expect(SubmissionStatus::DRAFT->canTransitionTo(SubmissionStatus::VERIFIED))->toBeFalse();
    expect(SubmissionStatus::GRADED->canTransitionTo(SubmissionStatus::DRAFT))->toBeFalse();
});
