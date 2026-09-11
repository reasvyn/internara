<?php

declare(strict_types=1);

use App\Modules\Assignment\Enums\AssignmentStatus;
use App\Modules\Certification\Domain\Certificate\Enums\CertificateStatus;

describe('J0M04: CertificateStatus enum', function (): void {
    test('J0M04-FR-CERT-017: cases carry the specified backing values', function (): void {
        expect(CertificateStatus::ISSUED->value)->toBe('issued');
        expect(CertificateStatus::from('issued'))->toBe(CertificateStatus::ISSUED);
        expect(CertificateStatus::REVOKED->value)->toBe('revoked');
        expect(CertificateStatus::from('revoked'))->toBe(CertificateStatus::REVOKED);
        expect(CertificateStatus::cases())->toHaveCount(2);
        expect(CertificateStatus::tryFrom('no-such-value'))->toBeNull();
    });
    test('J0M04-FR-CERT-017: labels resolve in English', function (): void {
        app()->setLocale('en');

        expect(CertificateStatus::ISSUED->label())->toBe('Issued');
        expect(CertificateStatus::REVOKED->label())->toBe('Revoked');
    });

    test('J0M04-FR-CERT-017: labels resolve in Indonesian', function (): void {
        app()->setLocale('id');

        expect(CertificateStatus::ISSUED->label())->toBe('Diterbitkan');
        expect(CertificateStatus::REVOKED->label())->toBe('Dicabut');
    });
    test('J0M04-FR-CERT-017: revoked is the only terminal state', function (): void {
        expect(CertificateStatus::REVOKED->isTerminal())->toBeTrue();
        expect(CertificateStatus::ISSUED->isTerminal())->toBeFalse();
    });

    test('J0M04-FR-CERT-017: validTransitions pins issued to revoked', function (): void {
        expect(CertificateStatus::ISSUED->validTransitions())->toBe([CertificateStatus::REVOKED]);
        expect(CertificateStatus::REVOKED->validTransitions())->toBe([]);
    });

    test('J0M04-FR-CERT-018: revoked certificates never transition onward', function (): void {
        expect(CertificateStatus::ISSUED->canTransitionTo(CertificateStatus::REVOKED))->toBeTrue();
        expect(CertificateStatus::REVOKED->canTransitionTo(CertificateStatus::ISSUED))->toBeFalse();
        expect(CertificateStatus::REVOKED->canTransitionTo(CertificateStatus::REVOKED))->toBeFalse();
        expect(CertificateStatus::ISSUED->canTransitionTo(AssignmentStatus::CLOSED))->toBeFalse();
    });
});
