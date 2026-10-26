<?php

declare(strict_types=1);

use App\Modules\Certification\Domain\Certificate\Entities\CertificateEntity;
use App\Modules\Certification\Domain\Certificate\Enums\CertificateStatus;
use App\Modules\Certification\Domain\Certificate\Models\Certificate;
use App\Modules\Core\Exceptions\RejectedException;

describe('J0M04: CertificateEntity', function (): void {
    test('J0M04-FR-CERT-005: serial is PREFIX/YEAR/0001 with a four-digit sequence', function (): void {
        expect(CertificateEntity::composeSerial('SMK Negeri 1 Bandung', 2026, 1))
            ->toBe('SMKNEG/2026/0001')
            ->and(CertificateEntity::composeSerial('SMK Negeri 1 Bandung', 2026, 42))
            ->toBe('SMKNEG/2026/0042')
            ->and(CertificateEntity::composeSerial('SMK Negeri 1 Bandung', 2026, 12345))
            ->toBe('SMKNEG/2026/12345');
    });

    test('J0M04-FR-CERT-005: the prefix is upper-cased, alphanumeric, and six characters at most', function (): void {
        expect(CertificateEntity::composePrefix('pt maju jaya'))->toBe('PTMAJU')
            ->and(CertificateEntity::composePrefix('PT. Maju-Jaya 99'))->toBe('PTMAJU')
            ->and(CertificateEntity::composePrefix('Klinik'))->toBe('KLINIK');
    });

    test('J0M04-FR-CERT-005: a nameless internship falls back to the PKL prefix', function (): void {
        expect(CertificateEntity::composePrefix(''))->toBe('PKL')
            ->and(CertificateEntity::composePrefix('   '))->toBe('PKL')
            ->and(CertificateEntity::composeSerial('', 2026, 7))->toBe('PKL/2026/0007');
    });

    test('J0M04-FR-CERT-005: the verification hash is a 64-character deterministic digest', function (): void {
        $hash = CertificateEntity::composeQrHash('student-1', 'SMK N 1', '88.5', 'admin-1', 'SMKNEG/2026/0001');

        expect($hash)->toHaveLength(64)
            ->and($hash)->toBe(CertificateEntity::composeQrHash('student-1', 'SMK N 1', '88.5', 'admin-1', 'SMKNEG/2026/0001'));
    });

    test('J0M04-FR-CERT-005: every hash input changes the digest', function (): void {
        $base = CertificateEntity::composeQrHash('student-1', 'SMK N 1', '88.5', 'admin-1', 'SMKNEG/2026/0001');

        foreach ([
            CertificateEntity::composeQrHash('student-2', 'SMK N 1', '88.5', 'admin-1', 'SMKNEG/2026/0001'),
            CertificateEntity::composeQrHash('student-1', 'SMK N 2', '88.5', 'admin-1', 'SMKNEG/2026/0001'),
            CertificateEntity::composeQrHash('student-1', 'SMK N 1', '90', 'admin-1', 'SMKNEG/2026/0001'),
            CertificateEntity::composeQrHash('student-1', 'SMK N 1', '88.5', 'admin-2', 'SMKNEG/2026/0001'),
            CertificateEntity::composeQrHash('student-1', 'SMK N 1', '88.5', 'admin-1', 'SMKNEG/2026/0002'),
        ] as $variant) {
            expect($variant)->not->toBe($base);
        }
    });

    test('J0M04-FR-CERT-006: a serial already on record is rejected before the write', function (): void {
        app()->setLocale('en');
        $draft = CertificateEntity::forIssuance('SMKNEG/2026/0001', str_repeat('a', 64));

        expect(fn () => $draft->assertUniqueAgainst(['SMKNEG/2026/0001'], []))
            ->toThrow(RejectedException::class, 'Certificate number SMKNEG/2026/0001 is already on record.');
    });

    test('J0M04-FR-CERT-006: a verification hash already on record is rejected before the write', function (): void {
        app()->setLocale('en');
        $hash = str_repeat('b', 64);
        $draft = CertificateEntity::forIssuance('SMKNEG/2026/0002', $hash);

        expect(fn () => $draft->assertUniqueAgainst([], [$hash]))
            ->toThrow(RejectedException::class);
    });

    test('J0M04-FR-CERT-006: the rejection is translated for the Indonesian locale', function (): void {
        app()->setLocale('id');
        $draft = CertificateEntity::forIssuance('SMKNEG/2026/0003', str_repeat('c', 64));

        expect(fn () => $draft->assertUniqueAgainst(['SMKNEG/2026/0003'], []))
            ->toThrow(RejectedException::class, 'Nomor sertifikat SMKNEG/2026/0003 sudah tercatat.');

        app()->setLocale('en');
    });

    test('J0M04-FR-CERT-006: a fresh serial and hash pass the invariant', function (): void {
        $draft = CertificateEntity::forIssuance('SMKNEG/2026/0004', str_repeat('d', 64));

        $draft->assertUniqueAgainst(['SMKNEG/2026/0001'], [str_repeat('e', 64)]);

        expect($draft->certificateNumber())->toBe('SMKNEG/2026/0004')
            ->and($draft->qrHash())->toHaveLength(64)
            ->and($draft->id())->toBe('');
    });

    test('J0M04-FR-CERT-017: only an issued certificate is revocable', function (): void {
        $attributes = ['registration_id' => '01a00000-0000-7000-8000-000000000009', 'issued_by' => null];

        $issued = Certificate::factory()->make([...$attributes, 'status' => CertificateStatus::ISSUED]);
        $revoked = Certificate::factory()->make([...$attributes, 'status' => CertificateStatus::REVOKED]);

        expect($issued->asCertificateEntity()->isRevocable())->toBeTrue()
            ->and($revoked->asCertificateEntity()->isRevocable())->toBeFalse();
    });

    test('J0M04-FR-CERT-008: the entity exposes the issuance audit metadata', function (): void {
        $entity = Certificate::factory()->make([
            'certificate_number' => 'SMKNEG/2026/0011',
            'registration_id' => '01a00000-0000-7000-8000-000000000001',
            'issued_by' => null,
        ])->asCertificateEntity();

        expect($entity->auditMetadata())->toBe([
            'certificate_number' => 'SMKNEG/2026/0011',
            'registration_id' => '01a00000-0000-7000-8000-000000000001',
        ])->and($entity->belongsToRegistration('01a00000-0000-7000-8000-000000000001'))->toBeTrue()
            ->and($entity->belongsToRegistration('01a00000-0000-7000-8000-000000000002'))->toBeFalse();
    });
});
