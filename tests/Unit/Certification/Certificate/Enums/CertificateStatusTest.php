<?php

declare(strict_types=1);

use App\Certification\Certificate\Enums\CertificateStatus;

test('certificate status has issued and revoked cases', function () {
    expect(CertificateStatus::cases())->toHaveCount(2);
    expect(CertificateStatus::ISSUED->value)->toBe('issued');
    expect(CertificateStatus::REVOKED->value)->toBe('revoked');
});

test('certificate status labels are non-empty', function () {
    foreach (CertificateStatus::cases() as $s) {
        expect($s->label())->toBeString()->not->toBeEmpty();
    }
});

test('only revoked is terminal', function () {
    expect(CertificateStatus::ISSUED->isTerminal())->toBeFalse();
    expect(CertificateStatus::REVOKED->isTerminal())->toBeTrue();
});

test('issued can transition to revoked', function () {
    expect(CertificateStatus::ISSUED->validTransitions())->toContain(CertificateStatus::REVOKED);
    expect(CertificateStatus::ISSUED->canTransitionTo(CertificateStatus::REVOKED))->toBeTrue();
});

test('revoked cannot transition', function () {
    expect(CertificateStatus::REVOKED->validTransitions())->toBe([]);
    expect(CertificateStatus::REVOKED->canTransitionTo(CertificateStatus::ISSUED))->toBeFalse();
});
