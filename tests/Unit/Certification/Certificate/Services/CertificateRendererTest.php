<?php

declare(strict_types=1);

use App\Certification\Certificate\Models\Certificate;
use App\Certification\Certificate\Services\CertificateRenderer;
use App\Enrollment\Registration\Models\Registration;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->renderer = app(CertificateRenderer::class);
});

test('resolvePlaceholders returns expected keys', function () {
    $certificate = Certificate::factory()->create([
        'certificate_number' => 'CERT-001',
    ]);
    $registration = Registration::factory()->create();

    $placeholders = $this->renderer->resolvePlaceholders($registration, $certificate);

    expect($placeholders)->toHaveKeys([
        '{student_name}',
        '{certificate_number}',
        '{issued_date}',
        '{start_date}',
        '{end_date}',
        '{score}',
        '{duration}',
    ]);
    expect($placeholders['{certificate_number}'])->toBe('CERT-001');
});

test('renderHtml returns non-empty string', function () {
    $certificate = Certificate::factory()->create();
    $registration = Registration::factory()->create();

    $html = $this->renderer->renderHtml($registration, $certificate);

    expect($html)->toBeString();
    expect(strlen($html))->toBeGreaterThan(0);
});
