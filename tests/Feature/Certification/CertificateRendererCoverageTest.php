<?php

declare(strict_types=1);

use App\Modules\Certification\Domain\Certificate\Models\Certificate;
use App\Modules\Certification\Domain\Certificate\Models\CertificateTemplate;
use App\Modules\Certification\Domain\Certificate\Services\CertificateRenderer;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

function j0m04dCertificate(array $overrides = []): Certificate
{
    return Certificate::factory()->create([
        'template_content' => '<p>{student_name}</p>',
        ...$overrides,
    ]);
}

describe('J0M04: certificate renderer', function (): void {
    test('J0M04-FR-CERT-003: resolver exposes every canonical placeholder', function (): void {
        $registration = Registration::factory()->create();
        $certificate = j0m04dCertificate(['registration_id' => $registration->id]);

        $pairs = app(CertificateRenderer::class)->resolvePlaceholders($registration, $certificate);

        expect($pairs)->toHaveKeys([
            '{student_name}', '{student_nis}', '{school_name}', '{school_code}',
            '{department_name}', '{company_name}', '{internship_name}', '{start_date}',
            '{end_date}', '{duration}', '{score}', '{score_letter}', '{certificate_number}',
            '{issued_date}', '{principal_name}', '{supervisor_name}',
        ]);
    });

    test('J0M04-FR-CERT-003: resolver replaces unknown relationships with safe placeholders', function (): void {
        $registration = Registration::factory()->create();
        $certificate = j0m04dCertificate(['registration_id' => $registration->id]);

        $pairs = app(CertificateRenderer::class)->resolvePlaceholders($registration, $certificate);

        expect($pairs['{school_name}'])->not->toBeEmpty()
            ->and($pairs['{company_name}'])->toBe('—')
            ->and($pairs['{supervisor_name}'])->toBe('—');
    });

    test('J0M04-FR-CERT-003: missing assessment data uses the documented safe score values', function (): void {
        $registration = Registration::factory()->create();
        $pairs = app(CertificateRenderer::class)->resolvePlaceholders($registration, j0m04dCertificate());

        expect($pairs['{score}'])->toBe('—')->and($pairs['{score_letter}'])->toBe('—');
    });

    test('J0M04-FR-CERT-003: issued date is rendered with the certificate locale format', function (): void {
        $registration = Registration::factory()->create();
        $certificate = j0m04dCertificate(['issued_at' => '2026-01-15 10:00:00']);

        expect(app(CertificateRenderer::class)->resolvePlaceholders($registration, $certificate)['{issued_date}'])
            ->toBe('15 January 2026');
    });

    test('J0M04-FR-CERT-003: duration is expressed in whole months', function (): void {
        $registration = Registration::factory()->create([
            'start_date' => '2026-01-01',
            'end_date' => '2026-04-01',
        ]);

        expect(app(CertificateRenderer::class)->resolvePlaceholders($registration, j0m04dCertificate())['{duration}'])
            ->toBe('3 months');
    });

    test('J0M04-FR-CERT-003: certificate number is copied into the resolver map', function (): void {
        $certificate = j0m04dCertificate(['certificate_number' => 'CERT-RESOLVED']);

        expect(app(CertificateRenderer::class)->resolvePlaceholders(Registration::factory()->create(), $certificate)['{certificate_number}'])
            ->toBe('CERT-RESOLVED');
    });

    test('J0M04-FR-CERT-007: renderHtml removes placeholders from the output', function (): void {
        $registration = Registration::factory()->create();
        $certificate = j0m04dCertificate([
            'registration_id' => $registration->id,
            'template_content' => '<h1>{certificate_number}</h1><p>{student_name}</p>',
        ]);

        $html = app(CertificateRenderer::class)->renderHtml($registration, $certificate);

        expect($html)->toContain($certificate->certificate_number)
            ->not->toContain('{certificate_number}')
            ->not->toContain('{student_name}');
    });

    test('J0M04-FR-CERT-015: renderPdf returns PDF bytes', function (): void {
        $registration = Registration::factory()->create();
        $certificate = j0m04dCertificate(['registration_id' => $registration->id]);

        $pdf = app(CertificateRenderer::class)->renderPdf($registration, $certificate);

        expect($pdf)->toStartWith('%PDF-')->not->toBeEmpty();
    });

    test('J0M04-FR-CERT-015: disk path resolves through the configured local disk', function (): void {
        Storage::fake('local');
        $renderer = app(CertificateRenderer::class);

        expect($renderer->getDiskPath('certificates/example.pdf'))
            ->toContain('certificates/example.pdf');
    });

    test('J0M04-FR-CERT-016: storing a PDF twice keeps one deterministic file', function (): void {
        Storage::fake('local');
        $registration = Registration::factory()->create();
        $certificate = j0m04dCertificate(['registration_id' => $registration->id]);
        $renderer = app(CertificateRenderer::class);

        $first = $renderer->storePdf($registration, $certificate);
        $second = $renderer->storePdf($registration, $certificate);

        expect($second)->toBe($first);
        Storage::disk('local')->assertExists($first);
    });

    test('J0M04-DD-CERT-001: template HTML is substituted rather than executed as a view name', function (): void {
        $registration = Registration::factory()->create();
        $certificate = j0m04dCertificate([
            'registration_id' => $registration->id,
            'template_content' => '<p>{student_name}</p><p>{{ $notDefined }}</p>',
        ]);

        expect(fn () => app(CertificateRenderer::class)->renderHtml($registration, $certificate))
            ->not->toThrow(Throwable::class);
    });

    test('J0M04-DD-CERT-003: PDF path is derived only from certificate number', function (): void {
        $first = j0m04dCertificate(['certificate_number' => 'CERT-A']);
        $second = j0m04dCertificate(['certificate_number' => 'CERT-B']);
        $renderer = app(CertificateRenderer::class);

        expect($renderer->pdfPath($first))->toBe('certificates/certificate_CERT-A.pdf')
            ->and($renderer->pdfPath($second))->toBe('certificates/certificate_CERT-B.pdf');
    });

    test('J0M04-DD-CERT-004: renderer uses the stored snapshot after template deletion', function (): void {
        $registration = Registration::factory()->create();
        $template = CertificateTemplate::factory()->create(['content_template' => '<p>Snapshot</p>']);
        $certificate = j0m04dCertificate([
            'registration_id' => $registration->id,
            'template_content' => $template->content_template,
        ]);
        $template->delete();

        expect(app(CertificateRenderer::class)->renderHtml($registration, $certificate))
            ->toContain('Snapshot');
    });
});
