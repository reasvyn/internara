<?php

declare(strict_types=1);

use App\Modules\Assignment\Enums\AssignmentStatus;
use App\Modules\Journals\Domain\Logbook\Enums\LogbookStatus;

describe('1KSWL: LogbookStatus enum', function (): void {
    test('1KSWL-FR-DAILY-003: cases carry the specified backing values', function (): void {
        expect(LogbookStatus::DRAFT->value)->toBe('draft');
        expect(LogbookStatus::from('draft'))->toBe(LogbookStatus::DRAFT);
        expect(LogbookStatus::SUBMITTED->value)->toBe('submitted');
        expect(LogbookStatus::from('submitted'))->toBe(LogbookStatus::SUBMITTED);
        expect(LogbookStatus::VERIFIED->value)->toBe('verified');
        expect(LogbookStatus::from('verified'))->toBe(LogbookStatus::VERIFIED);
        expect(LogbookStatus::REVISION_REQUIRED->value)->toBe('revision_required');
        expect(LogbookStatus::from('revision_required'))->toBe(LogbookStatus::REVISION_REQUIRED);
        expect(LogbookStatus::cases())->toHaveCount(4);
        expect(LogbookStatus::tryFrom('no-such-value'))->toBeNull();
    });
    test('1KSWL-FR-DAILY-003: labels resolve in English', function (): void {
        app()->setLocale('en');

        expect(LogbookStatus::DRAFT->label())->toBe('Draft');
        expect(LogbookStatus::SUBMITTED->label())->toBe('Submitted');
        expect(LogbookStatus::VERIFIED->label())->toBe('Verified');
        expect(LogbookStatus::REVISION_REQUIRED->label())->toBe('Revision Required');
    });

    test('1KSWL-FR-DAILY-003: labels resolve in Indonesian', function (): void {
        app()->setLocale('id');

        expect(LogbookStatus::DRAFT->label())->toBe('Draf');
        expect(LogbookStatus::SUBMITTED->label())->toBe('Dikirim');
        expect(LogbookStatus::VERIFIED->label())->toBe('Terverifikasi');
        expect(LogbookStatus::REVISION_REQUIRED->label())->toBe('Perlu Revisi');
    });
    test('1KSWL-FR-DAILY-003: only verified counts as finalized', function (): void {
        expect(LogbookStatus::VERIFIED->isFinalized())->toBeTrue();
        expect(LogbookStatus::DRAFT->isFinalized())->toBeFalse();
        expect(LogbookStatus::SUBMITTED->isFinalized())->toBeFalse();
        expect(LogbookStatus::REVISION_REQUIRED->isFinalized())->toBeFalse();
    });

    test('1KSWL-FR-DAILY-003: submitted and revision_required demand supervisor action', function (): void {
        expect(LogbookStatus::SUBMITTED->requiresAction())->toBeTrue();
        expect(LogbookStatus::REVISION_REQUIRED->requiresAction())->toBeTrue();
        expect(LogbookStatus::DRAFT->requiresAction())->toBeFalse();
        expect(LogbookStatus::VERIFIED->requiresAction())->toBeFalse();
    });

    test('1KSWL-FR-DAILY-003: verified is the only terminal state', function (): void {
        expect(LogbookStatus::VERIFIED->isTerminal())->toBeTrue();
        expect(LogbookStatus::DRAFT->isTerminal())->toBeFalse();
        expect(LogbookStatus::SUBMITTED->isTerminal())->toBeFalse();
        expect(LogbookStatus::REVISION_REQUIRED->isTerminal())->toBeFalse();
    });

    test('1KSWL-FR-DAILY-003: validTransitions pins the closed transition map', function (): void {
        expect(LogbookStatus::DRAFT->validTransitions())->toBe([LogbookStatus::SUBMITTED]);
        expect(LogbookStatus::SUBMITTED->validTransitions())->toBe([LogbookStatus::VERIFIED, LogbookStatus::REVISION_REQUIRED]);
        expect(LogbookStatus::REVISION_REQUIRED->validTransitions())->toBe([LogbookStatus::DRAFT]);
        expect(LogbookStatus::VERIFIED->validTransitions())->toBe([]);
    });

    test('1KSWL-FR-DAILY-003: canTransitionTo follows the map and rejects foreign enums', function (): void {
        expect(LogbookStatus::DRAFT->canTransitionTo(LogbookStatus::SUBMITTED))->toBeTrue();
        expect(LogbookStatus::SUBMITTED->canTransitionTo(LogbookStatus::VERIFIED))->toBeTrue();
        expect(LogbookStatus::REVISION_REQUIRED->canTransitionTo(LogbookStatus::DRAFT))->toBeTrue();
        expect(LogbookStatus::DRAFT->canTransitionTo(LogbookStatus::VERIFIED))->toBeFalse();
        expect(LogbookStatus::VERIFIED->canTransitionTo(LogbookStatus::DRAFT))->toBeFalse();
        expect(LogbookStatus::DRAFT->canTransitionTo(AssignmentStatus::DRAFT))->toBeFalse();
    });
});
