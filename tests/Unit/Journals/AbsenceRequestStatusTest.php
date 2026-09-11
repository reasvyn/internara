<?php

declare(strict_types=1);

use App\Modules\Assignment\Enums\AssignmentStatus;
use App\Modules\Journals\Domain\AbsenceRequest\Enums\AbsenceRequestStatus;

describe('1KSWL: AbsenceRequestStatus enum', function (): void {
    test('1KSWL-FR-DAILY-009: cases carry the specified backing values', function (): void {
        expect(AbsenceRequestStatus::PENDING->value)->toBe('pending');
        expect(AbsenceRequestStatus::from('pending'))->toBe(AbsenceRequestStatus::PENDING);
        expect(AbsenceRequestStatus::APPROVED->value)->toBe('approved');
        expect(AbsenceRequestStatus::from('approved'))->toBe(AbsenceRequestStatus::APPROVED);
        expect(AbsenceRequestStatus::REJECTED->value)->toBe('rejected');
        expect(AbsenceRequestStatus::from('rejected'))->toBe(AbsenceRequestStatus::REJECTED);
        expect(AbsenceRequestStatus::cases())->toHaveCount(3);
        expect(AbsenceRequestStatus::tryFrom('no-such-value'))->toBeNull();
    });
    test('1KSWL-FR-DAILY-009: labels resolve in English', function (): void {
        app()->setLocale('en');

        expect(AbsenceRequestStatus::PENDING->label())->toBe('Pending');
        expect(AbsenceRequestStatus::APPROVED->label())->toBe('Approved');
        expect(AbsenceRequestStatus::REJECTED->label())->toBe('Rejected');
    });

    test('1KSWL-FR-DAILY-009: labels resolve in Indonesian', function (): void {
        app()->setLocale('id');

        expect(AbsenceRequestStatus::PENDING->label())->toBe('Menunggu');
        expect(AbsenceRequestStatus::APPROVED->label())->toBe('Disetujui');
        expect(AbsenceRequestStatus::REJECTED->label())->toBe('Ditolak');
    });
    test('1KSWL-FR-DAILY-013: only approved and rejected count as processed', function (): void {
        expect(AbsenceRequestStatus::APPROVED->isProcessed())->toBeTrue();
        expect(AbsenceRequestStatus::REJECTED->isProcessed())->toBeTrue();
        expect(AbsenceRequestStatus::PENDING->isProcessed())->toBeFalse();
    });

    test('1KSWL-FR-DAILY-013: only pending transitions, to approved or rejected', function (): void {
        expect(AbsenceRequestStatus::APPROVED->isTerminal())->toBeTrue();
        expect(AbsenceRequestStatus::REJECTED->isTerminal())->toBeTrue();
        expect(AbsenceRequestStatus::PENDING->isTerminal())->toBeFalse();
        expect(AbsenceRequestStatus::PENDING->validTransitions())->toBe([AbsenceRequestStatus::APPROVED, AbsenceRequestStatus::REJECTED]);
        expect(AbsenceRequestStatus::APPROVED->validTransitions())->toBe([]);
        expect(AbsenceRequestStatus::PENDING->canTransitionTo(AbsenceRequestStatus::APPROVED))->toBeTrue();
        expect(AbsenceRequestStatus::PENDING->canTransitionTo(AbsenceRequestStatus::REJECTED))->toBeTrue();
        expect(AbsenceRequestStatus::APPROVED->canTransitionTo(AbsenceRequestStatus::PENDING))->toBeFalse();
        expect(AbsenceRequestStatus::PENDING->canTransitionTo(AssignmentStatus::DRAFT))->toBeFalse();
    });
});
