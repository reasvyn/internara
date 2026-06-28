<?php

declare(strict_types=1);

use App\Certification\Certificate\Models\Certificate;
use App\Certification\Certificate\Services\CertificateRenderer;
use App\Enrollment\Registration\Models\Registration;

test('resolves placeholders for certificate', function () {
    $renderer = app(CertificateRenderer::class);
    $registration = Registration::factory()->create();
    $certificate = Certificate::factory()->create();

    $placeholders = $renderer->resolvePlaceholders($registration, $certificate);

    expect($placeholders)->toHaveKeys([
        '{student_name}',
        '{certificate_number}',
        '{issued_date}',
    ]);
});

test('renders HTML from template', function () {
    $renderer = app(CertificateRenderer::class);
    $registration = Registration::factory()->create();
    $certificate = Certificate::factory()->create();

    $html = $renderer->renderHtml($registration, $certificate);

    expect($html)->toBeString();
    expect(strlen($html))->toBeGreaterThan(0);
});
