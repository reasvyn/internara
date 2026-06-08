<?php

declare(strict_types=1);

use App\Enrollment\Registration\Enums\RegistrationDocumentStatus;

describe('isTerminal', function () {
    it('verified is terminal', function () {
        expect(RegistrationDocumentStatus::VERIFIED->isTerminal())->toBeTrue();
    });
    it('rejected is terminal', function () {
        expect(RegistrationDocumentStatus::REJECTED->isTerminal())->toBeTrue();
    });
    it('pending is not terminal', function () {
        expect(RegistrationDocumentStatus::PENDING->isTerminal())->toBeFalse();
    });
});

describe('convenience methods', function () {
    it('isPending', function () {
        expect(RegistrationDocumentStatus::PENDING->isPending())->toBeTrue();
        expect(RegistrationDocumentStatus::VERIFIED->isPending())->toBeFalse();
    });
    it('isVerified', function () {
        expect(RegistrationDocumentStatus::VERIFIED->isVerified())->toBeTrue();
    });
    it('isRejected', function () {
        expect(RegistrationDocumentStatus::REJECTED->isRejected())->toBeTrue();
    });
});

test('label returns string for each status', function () {
    foreach (RegistrationDocumentStatus::cases() as $s) {
        expect($s->label())->toBeString();
    }
});