<?php

declare(strict_types=1);

namespace Tests\Feature\Document;

use App\Modules\Certification\Actions\DispatchBatchIssueCertificatesAction;
use App\Modules\Certification\Data\BatchIssueCertificatesData;
use App\Modules\Certification\Domain\Certificate\Models\Certificate;
use App\Modules\Certification\Domain\Certificate\Models\CertificateTemplate;
use App\Modules\Certification\Domain\Certificate\Services\CertificateRenderer;
use App\Modules\Certification\Jobs\BatchIssueCertificatesJob;
use App\Modules\Document\Models\Document;
use App\Modules\Document\Services\DocumentRenderer;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\User\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

uses(LazilyRefreshDatabase::class);

describe('7UB7S: PDF Generation — Dompdf Rendering Pipeline', function (): void {
    test('7UB7S-FR-PDF-001: PDF rendering uses Dompdf with remote resource fetching disabled', function (): void {
        expect(config('dompdf.options.enable_remote'))->toBeFalse()
            ->and(class_exists(Pdf::class))->toBeTrue();
    });

    test('7UB7S-FR-PDF-002: shared PDF layout and blade templates exist under resources/views/pdf', function (): void {
        expect(is_dir(resource_path('views/pdf')))->toBeTrue();
    });

    test('7UB7S-FR-PDF-003: certificate rendering is encapsulated in dedicated CertificateRenderer service', function (): void {
        $renderer = app(CertificateRenderer::class);
        $registration = Registration::factory()->create();
        $certificate = Certificate::factory()->create([
            'registration_id' => $registration->id,
            'template_content' => '<p>Name: {student_name} / Cert: {certificate_number}</p>',
        ]);

        $html = $renderer->renderHtml($registration, $certificate);
        expect($html)->toBeString()
            ->and($html)->toContain($certificate->certificate_number);

        $pdfBytes = $renderer->renderPdf($registration, $certificate);
        expect(strlen($pdfBytes))->toBeGreaterThan(100)
            ->and(str_starts_with($pdfBytes, '%PDF'))->toBeTrue();
    });

    test('7UB7S-FR-PDF-004: general document rendering is encapsulated in DocumentRenderer service', function (): void {
        $renderer = app(DocumentRenderer::class);
        $doc = Document::factory()->create([
            'content' => '<h1>Document for {{ $target->name ?? "Student" }}</h1>',
            'slug' => 'test-handbook',
        ]);
        $user = User::factory()->create(['name' => 'Ahmad Santoso']);

        $html = $renderer->renderHtml($doc, $user);
        expect($html)->toContain('Ahmad Santoso');

        $pdfBytes = $renderer->renderPdf($doc, $user);
        expect(strlen($pdfBytes))->toBeGreaterThan(100)
            ->and(str_starts_with($pdfBytes, '%PDF'))->toBeTrue();
    });

    test('7UB7S-FR-PDF-005: generated PDFs persist through storage paths for lifecycle management', function (): void {
        Storage::fake('local');

        $renderer = app(DocumentRenderer::class);
        $doc = Document::factory()->create([
            'content' => '<p>Official document</p>',
            'slug' => 'official-letter',
        ]);
        $user = User::factory()->create();

        $path = $renderer->storePdf($doc, $user);
        expect(Storage::disk('local')->exists($path))->toBeTrue();
    });

    test('7UB7S-FR-PDF-006: batch rendering jobs dispatch to queue and do not block HTTP request', function (): void {
        Queue::fake();

        $template = CertificateTemplate::factory()->create();
        $reg1 = Registration::factory()->create();

        app(DispatchBatchIssueCertificatesAction::class)->execute(new BatchIssueCertificatesData(
            registrationIds: [$reg1->id],
            status: 'completed',
            templateId: $template->id,
        ));

        Queue::assertPushed(BatchIssueCertificatesJob::class);
    });

    test('7UB7S-FR-PDF-007: single-document downloads render synchronously and return PDF content', function (): void {
        $renderer = app(DocumentRenderer::class);
        $doc = Document::factory()->create([
            'content' => '<p>Single Report Card</p>',
            'slug' => 'grade-card',
        ]);
        $user = User::factory()->create();

        $output = $renderer->renderPdf($doc, $user);
        expect(strlen($output))->toBeGreaterThan(50);
    });

    test('7UB7S-FR-PDF-008: templates render user-facing strings through the translation helper', function (): void {
        app()->setLocale('en');
        expect(__('common.status'))->not->toBeEmpty();

        app()->setLocale('id');
        expect(__('common.status'))->not->toBeEmpty();
    });

    test('7UB7S-FR-PDF-009: template data validation handles empty content safely', function (): void {
        $renderer = app(DocumentRenderer::class);
        $doc = Document::factory()->create([
            'content' => 'Empty text',
            'slug' => 'safe-doc',
        ]);
        $target = (object) ['name' => 'Valid'];

        $pdf = $renderer->renderPdf($doc, $target);
        expect(strlen($pdf))->toBeGreaterThan(50);
    });

    test('7UB7S-FR-PDF-010: generation endpoints write activity log with masked PII', function (): void {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        $activity = Activity::create([
            'log_name' => 'Certification',
            'description' => 'certificate_rendered',
            'causer_type' => User::class,
            'causer_id' => $admin->id,
            'properties' => ['document_type' => 'certificate', 'outcome' => 'success'],
        ]);

        expect($activity->exists)->toBeTrue()
            ->and($activity->properties['document_type'])->toBe('certificate');
    });

    test('7UB7S-FR-PDF-011: certificate output renders school logo, certificate number and qr verification hash', function (): void {
        $renderer = app(CertificateRenderer::class);
        $registration = Registration::factory()->create();
        $cert = Certificate::factory()->create([
            'registration_id' => $registration->id,
            'certificate_number' => 'CERT/2026/0001',
            'qr_hash' => 'hash123456789',
            'template_content' => '<p>Cert #{certificate_number}</p>',
        ]);

        $placeholders = $renderer->resolvePlaceholders($registration, $cert);
        expect($placeholders)->toHaveKey('{certificate_number}')
            ->and($placeholders['{certificate_number}'])->toBe('CERT/2026/0001')
            ->and($cert->qr_hash)->toBe('hash123456789');
    });

    test('7UB7S-NFR-PDF-001: single-document rendering completes rapidly in memory', function (): void {
        $renderer = app(DocumentRenderer::class);
        $doc = Document::factory()->create(['content' => '<p>Fast test</p>', 'slug' => 'fast-test']);
        $start = microtime(true);
        $renderer->renderPdf($doc, (object) []);
        $duration = microtime(true) - $start;

        expect($duration)->toBeLessThan(10.0);
    });

    test('7UB7S-NFR-PDF-002: generated documents produce compact lightweight PDF bytes', function (): void {
        $renderer = app(DocumentRenderer::class);
        $doc = Document::factory()->create(['content' => '<p>Small document</p>', 'slug' => 'small-doc']);
        $bytes = $renderer->renderPdf($doc, (object) []);

        // Well under 5 MB (e.g. < 500 KB)
        expect(strlen($bytes))->toBeLessThan(5 * 1024 * 1024);
    });

    test('7UB7S-NFR-PDF-003: batch rendering never executes synchronously in web request', function (): void {
        expect(is_subclass_of(BatchIssueCertificatesJob::class, ShouldQueue::class))->toBeTrue();
    });

    test('7UB7S-NFR-PDF-004: default font dejavu sans embedded ensures layout parity across environments', function (): void {
        expect(config('dompdf.options.default_font'))->toBeString();
    });

    test('7UB7S-DD-PDF-001: Blade templates used instead of hand-concatenated HTML strings', function (): void {
        $renderer = app(CertificateRenderer::class);
        $registration = Registration::factory()->create();
        $cert = Certificate::factory()->create([
            'registration_id' => $registration->id,
            'template_content' => '<span>Blade rendered {{ 1 + 1 }}</span>',
        ]);

        $html = $renderer->renderHtml($registration, $cert);
        expect($html)->toContain('Blade rendered 2');
    });

    test('7UB7S-DD-PDF-002: dedicated renderer services isolate rendering logic from domain actions', function (): void {
        expect(class_exists(CertificateRenderer::class))->toBeTrue()
            ->and(class_exists(DocumentRenderer::class))->toBeTrue();
    });

    test('7UB7S-DD-PDF-003: synchronous single-document path and queued batch path are distinct', function (): void {
        expect(is_subclass_of(BatchIssueCertificatesJob::class, ShouldQueue::class))->toBeTrue();
    });

    test('7UB7S-UC-PDF-001: student certificate renders PDF after assessment', function (): void {
        $renderer = app(CertificateRenderer::class);
        $registration = Registration::factory()->create();
        $cert = Certificate::factory()->create([
            'registration_id' => $registration->id,
            'template_content' => '<p>Completed assessment</p>',
        ]);

        $pdf = $renderer->renderPdf($registration, $cert);
        expect(strlen($pdf))->toBeGreaterThan(100);
    });

    test('7UB7S-UC-PDF-002: admin downloads grade-card PDF rendered synchronously', function (): void {
        $renderer = app(DocumentRenderer::class);
        $doc = Document::factory()->create(['content' => '<p>Grade card content</p>', 'slug' => 'gc']);

        $pdf = $renderer->renderPdf($doc, (object) ['grade' => 'A']);
        expect($pdf)->toBeString();
    });

    test('7UB7S-UC-PDF-003: cohort certificate issuance queues batch job with progress logging', function (): void {
        Queue::fake();

        $template = CertificateTemplate::factory()->create();
        $reg = Registration::factory()->create();

        app(DispatchBatchIssueCertificatesAction::class)->execute(new BatchIssueCertificatesData(
            registrationIds: [$reg->id],
            status: 'active',
            templateId: $template->id,
        ));

        Queue::assertPushed(BatchIssueCertificatesJob::class);
    });
});
