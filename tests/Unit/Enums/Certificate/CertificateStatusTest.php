<?php

declare(strict_types=1);

use App\Enums\Certificate\CertificateStatus;

describe('CertificateStatus', function () {
    it('returns correct labels', function () {
        expect(CertificateStatus::ISSUED->label())->toBe('Issued');
        expect(CertificateStatus::REVOKED->label())->toBe('Revoked');
    });

    it('revoked is terminal', function () {
        expect(CertificateStatus::REVOKED->isTerminal())->toBeTrue();
        expect(CertificateStatus::ISSUED->isTerminal())->toBeFalse();
    });
});
