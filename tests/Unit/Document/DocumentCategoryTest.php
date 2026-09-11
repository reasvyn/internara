<?php

declare(strict_types=1);

use App\Modules\Document\Enums\DocumentCategory;

describe('PKYX6: DocumentCategory enum', function (): void {
    test('PKYX6-FR-DOC-005: cases carry the specified backing values', function (): void {
        expect(DocumentCategory::APPLICATION->value)->toBe('application');
        expect(DocumentCategory::from('application'))->toBe(DocumentCategory::APPLICATION);
        expect(DocumentCategory::PERMIT->value)->toBe('permit');
        expect(DocumentCategory::from('permit'))->toBe(DocumentCategory::PERMIT);
        expect(DocumentCategory::CERTIFICATE->value)->toBe('certificate');
        expect(DocumentCategory::from('certificate'))->toBe(DocumentCategory::CERTIFICATE);
        expect(DocumentCategory::REPORT->value)->toBe('report');
        expect(DocumentCategory::from('report'))->toBe(DocumentCategory::REPORT);
        expect(DocumentCategory::LETTER->value)->toBe('letter');
        expect(DocumentCategory::from('letter'))->toBe(DocumentCategory::LETTER);
        expect(DocumentCategory::POLICY->value)->toBe('policy');
        expect(DocumentCategory::from('policy'))->toBe(DocumentCategory::POLICY);
        expect(DocumentCategory::HANDBOOK->value)->toBe('handbook');
        expect(DocumentCategory::from('handbook'))->toBe(DocumentCategory::HANDBOOK);
        expect(DocumentCategory::cases())->toHaveCount(7);
        expect(DocumentCategory::tryFrom('no-such-value'))->toBeNull();
    });
    test('PKYX6-FR-DOC-005: labels resolve in English', function (): void {
        app()->setLocale('en');

        expect(DocumentCategory::APPLICATION->label())->toBe('Application');
        expect(DocumentCategory::PERMIT->label())->toBe('Permit');
        expect(DocumentCategory::CERTIFICATE->label())->toBe('Certificate');
        expect(DocumentCategory::REPORT->label())->toBe('Report');
        expect(DocumentCategory::LETTER->label())->toBe('Letter');
        expect(DocumentCategory::POLICY->label())->toBe('Policy');
        expect(DocumentCategory::HANDBOOK->label())->toBe('Handbook');
    });

    test('PKYX6-FR-DOC-005: labels resolve in Indonesian', function (): void {
        app()->setLocale('id');

        expect(DocumentCategory::APPLICATION->label())->toBe('Permohonan');
        expect(DocumentCategory::PERMIT->label())->toBe('Surat Izin');
        expect(DocumentCategory::CERTIFICATE->label())->toBe('Sertifikat');
        expect(DocumentCategory::REPORT->label())->toBe('Laporan');
        expect(DocumentCategory::LETTER->label())->toBe('Surat');
        expect(DocumentCategory::POLICY->label())->toBe('Kebijakan');
        expect(DocumentCategory::HANDBOOK->label())->toBe('Buku Panduan');
    });
});
