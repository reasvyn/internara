<?php

declare(strict_types=1);

use App\Modules\Assignment\Enums\AssignmentStatus;
use App\Modules\Core\Contracts\LabelEnum;
use App\Modules\Core\Contracts\StatusEnum;
use App\Modules\Enrollment\Domain\Registration\Enums\RegistrationDocumentStatus;

describe('MBB5R: RegistrationDocumentStatus enum', function (): void {
    test('MBB5R-FR-REG-021: cases carry the specified backing values', function (): void {
        expect(RegistrationDocumentStatus::PENDING->value)->toBe('pending');
        expect(RegistrationDocumentStatus::from('pending'))->toBe(RegistrationDocumentStatus::PENDING);
        expect(RegistrationDocumentStatus::VERIFIED->value)->toBe('verified');
        expect(RegistrationDocumentStatus::from('verified'))->toBe(RegistrationDocumentStatus::VERIFIED);
        expect(RegistrationDocumentStatus::REJECTED->value)->toBe('rejected');
        expect(RegistrationDocumentStatus::from('rejected'))->toBe(RegistrationDocumentStatus::REJECTED);
        expect(RegistrationDocumentStatus::cases())->toHaveCount(3);
        expect(RegistrationDocumentStatus::tryFrom('no-such-value'))->toBeNull();
    });
    test('MBB5R-FR-REG-021: labels resolve in English', function (): void {
        app()->setLocale('en');

        expect(RegistrationDocumentStatus::PENDING->label())->toBe('Pending');
        expect(RegistrationDocumentStatus::VERIFIED->label())->toBe('Verified');
        expect(RegistrationDocumentStatus::REJECTED->label())->toBe('Rejected');
    });

    test('MBB5R-FR-REG-021: labels resolve in Indonesian', function (): void {
        app()->setLocale('id');

        expect(RegistrationDocumentStatus::PENDING->label())->toBe('Menunggu');
        expect(RegistrationDocumentStatus::VERIFIED->label())->toBe('Terverifikasi');
        expect(RegistrationDocumentStatus::REJECTED->label())->toBe('Ditolak');
    });
    test('MBB5R-FR-REG-022: the enum signs the label and status contracts', function (): void {
        expect(RegistrationDocumentStatus::PENDING)->toBeInstanceOf(LabelEnum::class);
        expect(RegistrationDocumentStatus::PENDING)->toBeInstanceOf(StatusEnum::class);
        expect(RegistrationDocumentStatus::VERIFIED->value)->toBe('verified');
    });

    test('MBB5R-FR-REG-021: state predicates match exactly one case each', function (): void {
        expect(RegistrationDocumentStatus::PENDING->isPending())->toBeTrue();
        expect(RegistrationDocumentStatus::VERIFIED->isPending())->toBeFalse();
        expect(RegistrationDocumentStatus::VERIFIED->isVerified())->toBeTrue();
        expect(RegistrationDocumentStatus::REJECTED->isVerified())->toBeFalse();
        expect(RegistrationDocumentStatus::REJECTED->isRejected())->toBeTrue();
        expect(RegistrationDocumentStatus::PENDING->isRejected())->toBeFalse();
    });

    test('MBB5R-FR-REG-021: verified and rejected are terminal with a pending-only fan-out', function (): void {
        expect(RegistrationDocumentStatus::VERIFIED->isTerminal())->toBeTrue();
        expect(RegistrationDocumentStatus::REJECTED->isTerminal())->toBeTrue();
        expect(RegistrationDocumentStatus::PENDING->isTerminal())->toBeFalse();
        expect(RegistrationDocumentStatus::PENDING->validTransitions())->toBe([RegistrationDocumentStatus::VERIFIED, RegistrationDocumentStatus::REJECTED]);
        expect(RegistrationDocumentStatus::VERIFIED->validTransitions())->toBe([]);
        expect(RegistrationDocumentStatus::PENDING->canTransitionTo(RegistrationDocumentStatus::VERIFIED))->toBeTrue();
        expect(RegistrationDocumentStatus::VERIFIED->canTransitionTo(RegistrationDocumentStatus::PENDING))->toBeFalse();
        expect(RegistrationDocumentStatus::PENDING->canTransitionTo(AssignmentStatus::DRAFT))->toBeFalse();
    });
});
