<?php

declare(strict_types=1);

use App\Modules\Assignment\Enums\AssignmentStatus;
use App\Modules\Core\Contracts\LabelEnum;
use App\Modules\Core\Contracts\StatusEnum;
use App\Modules\Enrollment\Domain\AccountApplication\Enums\AccountApplicationStatus;

describe('920SO: AccountApplicationStatus enum', function (): void {
    test('920SO-FR-APPLY-001: cases carry the specified backing values', function (): void {
        expect(AccountApplicationStatus::PENDING->value)->toBe('pending');
        expect(AccountApplicationStatus::from('pending'))->toBe(AccountApplicationStatus::PENDING);
        expect(AccountApplicationStatus::APPROVED->value)->toBe('approved');
        expect(AccountApplicationStatus::from('approved'))->toBe(AccountApplicationStatus::APPROVED);
        expect(AccountApplicationStatus::REJECTED->value)->toBe('rejected');
        expect(AccountApplicationStatus::from('rejected'))->toBe(AccountApplicationStatus::REJECTED);
        expect(AccountApplicationStatus::cases())->toHaveCount(3);
        expect(AccountApplicationStatus::tryFrom('no-such-value'))->toBeNull();
    });
    test('920SO-FR-APPLY-001: labels resolve in English', function (): void {
        app()->setLocale('en');

        expect(AccountApplicationStatus::PENDING->label())->toBe('Pending');
        expect(AccountApplicationStatus::APPROVED->label())->toBe('Approved');
        expect(AccountApplicationStatus::REJECTED->label())->toBe('Rejected');
    });

    test('920SO-FR-APPLY-001: labels resolve in Indonesian', function (): void {
        app()->setLocale('id');

        expect(AccountApplicationStatus::PENDING->label())->toBe('Tertunda');
        expect(AccountApplicationStatus::APPROVED->label())->toBe('Disetujui');
        expect(AccountApplicationStatus::REJECTED->label())->toBe('Ditolak');
    });
    test('920SO-FR-APPLY-001: the enum signs the label and status contracts', function (): void {
        expect(AccountApplicationStatus::PENDING)->toBeInstanceOf(LabelEnum::class);
        expect(AccountApplicationStatus::PENDING)->toBeInstanceOf(StatusEnum::class);
        expect(AccountApplicationStatus::PENDING->value)->toBe('pending');
    });

    test('920SO-FR-APPLY-002: approved and rejected are terminal', function (): void {
        expect(AccountApplicationStatus::APPROVED->isTerminal())->toBeTrue();
        expect(AccountApplicationStatus::REJECTED->isTerminal())->toBeTrue();
        expect(AccountApplicationStatus::PENDING->isTerminal())->toBeFalse();
    });

    test('920SO-FR-APPLY-002: pending moves to approved or rejected and nothing else', function (): void {
        expect(AccountApplicationStatus::PENDING->validTransitions())->toBe([AccountApplicationStatus::APPROVED, AccountApplicationStatus::REJECTED]);
        expect(AccountApplicationStatus::APPROVED->validTransitions())->toBe([]);
        expect(AccountApplicationStatus::REJECTED->validTransitions())->toBe([]);
        expect(AccountApplicationStatus::PENDING->canTransitionTo(AccountApplicationStatus::APPROVED))->toBeTrue();
        expect(AccountApplicationStatus::PENDING->canTransitionTo(AccountApplicationStatus::REJECTED))->toBeTrue();
        expect(AccountApplicationStatus::APPROVED->canTransitionTo(AccountApplicationStatus::PENDING))->toBeFalse();
        expect(AccountApplicationStatus::REJECTED->canTransitionTo(AccountApplicationStatus::APPROVED))->toBeFalse();
        expect(AccountApplicationStatus::PENDING->canTransitionTo(AssignmentStatus::CLOSED))->toBeFalse();
    });
});
