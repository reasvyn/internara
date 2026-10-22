<?php

declare(strict_types=1);

namespace Tests\Feature\Certification;

use App\Modules\Certification\Domain\Certificate\Enums\CertificateStatus;
use App\Modules\Certification\Domain\Certificate\Livewire\CertificateList;
use App\Modules\Certification\Domain\Certificate\Livewire\CertificateTemplateManager;
use App\Modules\Certification\Domain\Certificate\Livewire\StudentCertificates;
use App\Modules\Certification\Domain\Certificate\Models\Certificate;
use App\Modules\Certification\Domain\Certificate\Models\CertificateTemplate;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

describe('J0M05 / J0M04: Certification Livewire UI Components', function (): void {
    test('J0M05-FR-CERT-001: CertificateList renders headers and displays certificates', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $cert = Certificate::factory()->create([
            'certificate_number' => 'CERT-2026-TEST-001',
            'status' => CertificateStatus::ISSUED->value,
        ]);

        $test = Livewire::test(CertificateList::class);
        $test->assertOk()
            ->assertSee('CERT-2026-TEST-001');

        expect(collect($test->instance()->headers())->pluck('index')->all())
            ->toContain('certificate_number', 'student_name', 'status', 'issued_at');
    });

    test('J0M05-FR-CERT-002: CertificateList search narrows down by certificate number', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $cert1 = Certificate::factory()->create(['certificate_number' => 'CERT-ALPHA-999']);
        $cert2 = Certificate::factory()->create(['certificate_number' => 'CERT-BETA-888']);

        Livewire::test(CertificateList::class)
            ->set('search', 'CERT-ALPHA')
            ->assertSee('CERT-ALPHA-999')
            ->assertDontSee('CERT-BETA-888');
    });

    test('J0M05-FR-CERT-003: CertificateTemplateManager renders headers and handles create modal', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        CertificateTemplate::create([
            'name' => 'Default Vocational Diploma',
            'layout' => 'landscape',
            'content_template' => '<h1>Certificate of Completion</h1>',
            'is_active' => true,
        ]);

        $test = Livewire::test(CertificateTemplateManager::class);
        $test->assertOk()
            ->assertSee('Default Vocational Diploma');

        expect(collect($test->instance()->headers())->pluck('index')->all())
            ->toContain('name', 'layout', 'is_active');

        $test->call('create');
        expect($test->get('showModal'))->toBeTrue()
            ->and($test->get('formData')['name'])->toBe('');
    });

    test('J0M05-FR-CERT-004: CertificateTemplateManager search reaches name and layout', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        CertificateTemplate::create([
            'name' => 'Unique Landscape Template',
            'layout' => 'landscape',
            'content_template' => '<p>Template Content</p>',
            'is_active' => true,
        ]);

        CertificateTemplate::create([
            'name' => 'Other Template',
            'layout' => 'portrait',
            'content_template' => '<p>Other Content</p>',
            'is_active' => true,
        ]);

        Livewire::test(CertificateTemplateManager::class)
            ->set('search', 'Landscape')
            ->assertSee('Unique Landscape Template');
    });

    test('J0M05-FR-CERT-005: StudentCertificates renders certificates for student and aborts for non-student', function (): void {
        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);

        $test = Livewire::test(StudentCertificates::class);
        $test->assertOk()
            ->assertViewHas('certificates');

        // Non-student role fails boot check
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        Livewire::actingAs($teacher)
            ->test(StudentCertificates::class)
            ->assertForbidden();
    });
});
