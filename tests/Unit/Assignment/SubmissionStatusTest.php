<?php

declare(strict_types=1);

use App\Modules\Assignment\Domain\Submission\Enums\SubmissionStatus;
use App\Modules\Assignment\Enums\AssignmentStatus;

describe('T657Z: SubmissionStatus enum', function (): void {
    test('T657Z-FR-SUBM-009: cases carry the specified backing values', function (): void {
        expect(SubmissionStatus::DRAFT->value)->toBe('draft');
        expect(SubmissionStatus::from('draft'))->toBe(SubmissionStatus::DRAFT);
        expect(SubmissionStatus::SUBMITTED->value)->toBe('submitted');
        expect(SubmissionStatus::from('submitted'))->toBe(SubmissionStatus::SUBMITTED);
        expect(SubmissionStatus::VERIFIED->value)->toBe('verified');
        expect(SubmissionStatus::from('verified'))->toBe(SubmissionStatus::VERIFIED);
        expect(SubmissionStatus::GRADED->value)->toBe('graded');
        expect(SubmissionStatus::from('graded'))->toBe(SubmissionStatus::GRADED);
        expect(SubmissionStatus::REVISION_REQUIRED->value)->toBe('revision_required');
        expect(SubmissionStatus::from('revision_required'))->toBe(SubmissionStatus::REVISION_REQUIRED);
        expect(SubmissionStatus::cases())->toHaveCount(5);
        expect(SubmissionStatus::tryFrom('no-such-value'))->toBeNull();
    });
    test('T657Z-FR-SUBM-009: labels resolve in English', function (): void {
        app()->setLocale('en');

        expect(SubmissionStatus::DRAFT->label())->toBe('Draft');
        expect(SubmissionStatus::SUBMITTED->label())->toBe('Submitted');
        expect(SubmissionStatus::VERIFIED->label())->toBe('Verified');
        expect(SubmissionStatus::GRADED->label())->toBe('Graded');
        expect(SubmissionStatus::REVISION_REQUIRED->label())->toBe('Revision Required');
    });

    test('T657Z-FR-SUBM-009: labels resolve in Indonesian', function (): void {
        app()->setLocale('id');

        expect(SubmissionStatus::DRAFT->label())->toBe('Draf');
        expect(SubmissionStatus::SUBMITTED->label())->toBe('Dikirim');
        expect(SubmissionStatus::VERIFIED->label())->toBe('Terverifikasi');
        expect(SubmissionStatus::GRADED->label())->toBe('Dinilai');
        expect(SubmissionStatus::REVISION_REQUIRED->label())->toBe('Perlu Revisi');
    });
    test('T657Z-FR-SUBM-009: isFinalized is true for verified and graded only', function (): void {
        expect(SubmissionStatus::VERIFIED->isFinalized())->toBeTrue();
        expect(SubmissionStatus::GRADED->isFinalized())->toBeTrue();
        expect(SubmissionStatus::DRAFT->isFinalized())->toBeFalse();
        expect(SubmissionStatus::SUBMITTED->isFinalized())->toBeFalse();
        expect(SubmissionStatus::REVISION_REQUIRED->isFinalized())->toBeFalse();
    });

    test('T657Z-FR-SUBM-009: requiresAction is true for submitted and revision_required only', function (): void {
        expect(SubmissionStatus::SUBMITTED->requiresAction())->toBeTrue();
        expect(SubmissionStatus::REVISION_REQUIRED->requiresAction())->toBeTrue();
        expect(SubmissionStatus::DRAFT->requiresAction())->toBeFalse();
        expect(SubmissionStatus::VERIFIED->requiresAction())->toBeFalse();
        expect(SubmissionStatus::GRADED->requiresAction())->toBeFalse();
    });

    test('T657Z-FR-SUBM-009: verified and graded are the terminal states', function (): void {
        expect(SubmissionStatus::VERIFIED->isTerminal())->toBeTrue();
        expect(SubmissionStatus::GRADED->isTerminal())->toBeTrue();
        expect(SubmissionStatus::DRAFT->isTerminal())->toBeFalse();
        expect(SubmissionStatus::SUBMITTED->isTerminal())->toBeFalse();
        expect(SubmissionStatus::REVISION_REQUIRED->isTerminal())->toBeFalse();
    });

    test('T657Z-FR-SUBM-009: validTransitions pins the fixed transition matrix', function (): void {
        expect(SubmissionStatus::DRAFT->validTransitions())->toBe([SubmissionStatus::SUBMITTED]);
        expect(SubmissionStatus::SUBMITTED->validTransitions())->toBe([SubmissionStatus::VERIFIED, SubmissionStatus::GRADED, SubmissionStatus::REVISION_REQUIRED]);
        expect(SubmissionStatus::REVISION_REQUIRED->validTransitions())->toBe([SubmissionStatus::SUBMITTED]);
        expect(SubmissionStatus::VERIFIED->validTransitions())->toBe([]);
        expect(SubmissionStatus::GRADED->validTransitions())->toBe([]);
    });

    test('T657Z-FR-SUBM-009: canTransitionTo follows the matrix and rejects foreign enums', function (): void {
        expect(SubmissionStatus::DRAFT->canTransitionTo(SubmissionStatus::SUBMITTED))->toBeTrue();
        expect(SubmissionStatus::SUBMITTED->canTransitionTo(SubmissionStatus::GRADED))->toBeTrue();
        expect(SubmissionStatus::REVISION_REQUIRED->canTransitionTo(SubmissionStatus::SUBMITTED))->toBeTrue();
        expect(SubmissionStatus::DRAFT->canTransitionTo(SubmissionStatus::GRADED))->toBeFalse();
        expect(SubmissionStatus::VERIFIED->canTransitionTo(SubmissionStatus::SUBMITTED))->toBeFalse();
        expect(SubmissionStatus::DRAFT->canTransitionTo(AssignmentStatus::DRAFT))->toBeFalse();
    });
});
