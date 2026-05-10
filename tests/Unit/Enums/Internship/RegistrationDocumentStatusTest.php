<?php

declare(strict_types=1);

use App\Enums\Internship\RegistrationDocumentStatus;

it('returns correct label for pending', function () {
    expect(RegistrationDocumentStatus::PENDING->label())->toBe('Pending');
});

it('returns correct label for verified', function () {
    expect(RegistrationDocumentStatus::VERIFIED->label())->toBe('Verified');
});

it('returns correct label for rejected', function () {
    expect(RegistrationDocumentStatus::REJECTED->label())->toBe('Rejected');
});

it('detects pending status', function () {
    expect(RegistrationDocumentStatus::PENDING->isPending())->toBeTrue();
    expect(RegistrationDocumentStatus::VERIFIED->isPending())->toBeFalse();
    expect(RegistrationDocumentStatus::REJECTED->isPending())->toBeFalse();
});

it('detects verified status', function () {
    expect(RegistrationDocumentStatus::VERIFIED->isVerified())->toBeTrue();
    expect(RegistrationDocumentStatus::PENDING->isVerified())->toBeFalse();
    expect(RegistrationDocumentStatus::REJECTED->isVerified())->toBeFalse();
});

it('detects rejected status', function () {
    expect(RegistrationDocumentStatus::REJECTED->isRejected())->toBeTrue();
    expect(RegistrationDocumentStatus::PENDING->isRejected())->toBeFalse();
    expect(RegistrationDocumentStatus::VERIFIED->isRejected())->toBeFalse();
});
