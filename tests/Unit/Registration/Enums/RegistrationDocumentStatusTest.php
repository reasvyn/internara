<?php

declare(strict_types=1);

use App\Domain\Core\Contracts\LabelEnum;
use App\Domain\Core\Contracts\StatusEnum;
use App\Domain\Registration\Enums\RegistrationDocumentStatus;

describe('RegistrationDocumentStatus enum', function () {
    it('implements LabelEnum', function () {
        expect(RegistrationDocumentStatus::class)->toImplement(LabelEnum::class);
    });

    it('implements StatusEnum', function () {
        expect(RegistrationDocumentStatus::class)->toImplement(StatusEnum::class);
    });

    it('has labels', function () {
        expect(RegistrationDocumentStatus::PENDING->label())->toBe('Pending')
            ->and(RegistrationDocumentStatus::VERIFIED->label())->toBe('Verified')
            ->and(RegistrationDocumentStatus::REJECTED->label())->toBe('Rejected');
    });

    it('detects pending state', function () {
        expect(RegistrationDocumentStatus::PENDING->isPending())->toBeTrue()
            ->and(RegistrationDocumentStatus::VERIFIED->isPending())->toBeFalse();
    });

    it('detects terminal states', function () {
        expect(RegistrationDocumentStatus::VERIFIED->isTerminal())->toBeTrue()
            ->and(RegistrationDocumentStatus::REJECTED->isTerminal())->toBeTrue()
            ->and(RegistrationDocumentStatus::PENDING->isTerminal())->toBeFalse();
    });

    it('validates transitions', function () {
        expect(RegistrationDocumentStatus::PENDING->canTransitionTo(RegistrationDocumentStatus::VERIFIED))->toBeTrue()
            ->and(RegistrationDocumentStatus::PENDING->canTransitionTo(RegistrationDocumentStatus::REJECTED))->toBeTrue()
            ->and(RegistrationDocumentStatus::VERIFIED->canTransitionTo(RegistrationDocumentStatus::PENDING))->toBeFalse();
    });
});
