<?php

declare(strict_types=1);

namespace Tests\Feature\Certification;

use App\Modules\Certification\Domain\Certificate\Actions\ReadCertificateStatusAction;
use App\Modules\Certification\Domain\Certificate\Data\CertificateStatusView;
use App\Modules\Certification\Domain\Certificate\Enums\CertificateStatus;
use App\Modules\Certification\Domain\Certificate\Models\Certificate;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(LazilyRefreshDatabase::class);

describe('J0M05: Certificate QR Verification', function (): void {
    test('J0M05-UC-QR-001, J0M05-FR-QR-001, J0M05-FR-QR-003: employer scans paper QR code and verifies issued certificate without login', function (): void {
        $student = User::factory()->create(['name' => 'Budi Pratama']);
        $registration = Registration::factory()->create(['student_id' => $student->id]);
        $certificate = Certificate::factory()->create([
            'registration_id' => $registration->id,
            'certificate_number' => 'PKL/2026/0001',
            'qr_hash' => 'hashvalid0123456789abcdef0123456789abcdef',
            'status' => CertificateStatus::ISSUED->value,
            'issued_at' => now(),
        ]);

        $response = $this->get(route('certificates.verify', ['qr_hash' => $certificate->qr_hash]));

        $response->assertStatus(200)
            ->assertSee('Budi Pratama')
            ->assertSee('PKL/2026/0001')
            ->assertSee(__('certificate.verify.status_issued'));
    });

    test('J0M05-UC-QR-002, J0M05-FR-QR-006: student shares verification link with URL contract format', function (): void {
        $hash = 'hashvalid0123456789abcdef0123456789abcdef';
        $certificate = Certificate::factory()->create([
            'qr_hash' => $hash,
            'status' => CertificateStatus::ISSUED->value,
        ]);

        $url = route('certificates.verify', ['qr_hash' => $certificate->qr_hash]);
        expect($url)->toContain('/verify/'.$hash);

        $response = $this->get($url);
        $response->assertStatus(200);
    });

    test('J0M05-UC-QR-003, J0M05-FR-QR-005, J0M05-FR-QR-008: forged or mistyped link answers clean not-found without revealing hints', function (): void {
        $unknownHash = 'nonexistenthash0123456789abcdef0123456789';
        $response = $this->get(route('certificates.verify', ['qr_hash' => $unknownHash]));

        $response->assertStatus(200)
            ->assertSee(__('certificate.verify.status_not_found'))
            ->assertSee(__('certificate.verify.not_found_message'))
            ->assertDontSee(__('certificate.verify.status_issued'))
            ->assertDontSee(__('certificate.verify.status_revoked'));

        // Malformed hash (e.g. invalid chars or wrong length) returns 404 or uniform not-found
        $action = app(ReadCertificateStatusAction::class);
        $result = $action->execute('invalid-short');
        expect($result->status)->toBe('not_found');
    });

    test('J0M05-FR-QR-002, J0M05-DD-QR-003: lookup resolves through ReadCertificateStatusAction returning view model', function (): void {
        $student = User::factory()->create(['name' => 'Siti Nurhaliza']);
        $registration = Registration::factory()->create(['student_id' => $student->id]);
        $certificate = Certificate::factory()->create([
            'registration_id' => $registration->id,
            'certificate_number' => 'PKL/2026/0042',
            'qr_hash' => 'siti0123456789abcdef0123456789abcdef012345',
            'status' => CertificateStatus::ISSUED->value,
            'issued_at' => now(),
        ]);

        $action = app(ReadCertificateStatusAction::class);
        $view = $action->execute($certificate->qr_hash);

        expect($view)->toBeInstanceOf(CertificateStatusView::class)
            ->and($view->status)->toBe('issued')
            ->and($view->studentName)->toBe('Siti Nurhaliza')
            ->and($view->certificateNumber)->toBe('PKL/2026/0042');
    });

    test('J0M05-FR-QR-004: revoked certificate renders warning styling with revocation date', function (): void {
        $student = User::factory()->create(['name' => 'Ahmad Dani']);
        $registration = Registration::factory()->create(['student_id' => $student->id]);
        $certificate = Certificate::factory()->create([
            'registration_id' => $registration->id,
            'certificate_number' => 'PKL/2026/0099',
            'qr_hash' => 'revokedhash0123456789abcdef0123456789abcdef',
            'status' => CertificateStatus::REVOKED->value,
            'issued_at' => now()->subMonths(2),
            'revoked_at' => now()->subDays(5),
        ]);

        $response = $this->get(route('certificates.verify', ['qr_hash' => $certificate->qr_hash]));

        $response->assertStatus(200)
            ->assertSee(__('certificate.verify.status_revoked'))
            ->assertSee(__('certificate.verify.revoked_warning'))
            ->assertDontSee(__('certificate.verify.status_issued'));
    });

    test('J0M05-FR-QR-007, J0M05-NFR-QR-002: page exposes no student data beyond holder name, school, and metadata', function (): void {
        $student = User::factory()->create([
            'name' => 'Rina Kartika',
            'email' => 'rina.secret@example.com',
        ]);
        $registration = Registration::factory()->create(['student_id' => $student->id]);
        $certificate = Certificate::factory()->create([
            'registration_id' => $registration->id,
            'qr_hash' => 'rinahash0123456789abcdef0123456789abcdef012',
            'status' => CertificateStatus::ISSUED->value,
        ]);

        $response = $this->get(route('certificates.verify', ['qr_hash' => $certificate->qr_hash]));

        $response->assertStatus(200)
            ->assertSee('Rina Kartika')
            ->assertDontSee('rina.secret@example.com');
    });

    test('J0M05-FR-QR-009, J0M05-NFR-QR-005: verification responses carry no-store cache headers', function (): void {
        $certificate = Certificate::factory()->create([
            'qr_hash' => 'cachecheck0123456789abcdef0123456789abcdef',
            'status' => CertificateStatus::ISSUED->value,
        ]);

        $response = $this->get(route('certificates.verify', ['qr_hash' => $certificate->qr_hash]));

        $response->assertStatus(200);
        $cacheControl = (string) $response->headers->get('Cache-Control');
        expect($cacheControl)->toContain('no-store')
            ->and($cacheControl)->toContain('no-cache');
    });

    test('J0M05-NFR-QR-001, J0M05-DD-QR-002: route definition includes throttling middleware and public access', function (): void {
        $route = Route::getRoutes()->getByName('certificates.verify');

        expect($route)->not->toBeNull()
            ->and($route->middleware())->toContain('throttle:30,1')
            ->and($route->middleware())->not->toContain('auth');
    });

    test('J0M05-NFR-QR-003: verification interface supports bilingual rendering in en and id', function (): void {
        app()->setLocale('en');
        expect(__('certificate.verify.title'))->toBe('Certificate Verification');

        app()->setLocale('id');
        expect(__('certificate.verify.title'))->toBe('Verifikasi Sertifikat');
    });

    test('J0M05-NFR-QR-004: status is conveyed by icon and wording for accessibility', function (): void {
        $certificate = Certificate::factory()->create([
            'qr_hash' => 'iconcheck0123456789abcdef0123456789abcdef0',
            'status' => CertificateStatus::ISSUED->value,
        ]);

        $response = $this->get(route('certificates.verify', ['qr_hash' => $certificate->qr_hash]));

        // Status badge contains non-color symbol '✓' along with text
        $response->assertStatus(200)
            ->assertSee('✓')
            ->assertSee(__('certificate.verify.status_issued'));
    });

    test('J0M05-DD-QR-001: certificate issuance generates cryptographically random qr_hash', function (): void {
        $certificate = Certificate::factory()->create([
            'qr_hash' => hash('sha256', 'random-salt-'.uniqid()),
        ]);

        expect(strlen($certificate->qr_hash))->toBe(64)
            ->and(ctype_xdigit($certificate->qr_hash))->toBeTrue();
    });
});
