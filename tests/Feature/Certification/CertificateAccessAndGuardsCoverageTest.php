<?php

declare(strict_types=1);

use App\Modules\Certification\Domain\Certificate\Actions\RevokeCertificateAction;
use App\Modules\Certification\Domain\Certificate\Livewire\CertificateList;
use App\Modules\Certification\Domain\Certificate\Livewire\CertificateTemplateManager;
use App\Modules\Certification\Domain\Certificate\Models\Certificate;
use App\Modules\Certification\Domain\Certificate\Models\CertificateTemplate;
use App\Modules\Certification\Jobs\BatchIssueCertificatesJob;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroupMember;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

function j0m04eRole(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function j0m04eOwnedCertificate(User $student): Certificate
{
    $registration = Registration::factory()->create();
    InternshipGroupMember::factory()->create([
        'registration_id' => $registration->id,
        'user_id' => $student->id,
    ]);

    return j0m04eCertificate(['registration_id' => $registration->id]);
}

function j0m04eCertificate(array $overrides = []): Certificate
{
    return Certificate::factory()->create([
        'template_content' => '<p>{certificate_number}</p>',
        ...$overrides,
    ]);
}

describe('J0M04: certificate access and policy guards', function (): void {
    test('J0M04-FR-CERT-019: super admins can view the cohort and revoke certificates', function (): void {
        $superAdmin = j0m04eRole('super_admin');
        $certificate = j0m04eCertificate();
        $this->actingAs($superAdmin);

        expect(Gate::allows('viewAny', Certificate::class))->toBeTrue()
            ->and(Gate::allows('view', $certificate))->toBeTrue()
            ->and(Gate::allows('revoke', $certificate))->toBeTrue();
    });

    test('J0M04-FR-CERT-019: an unassigned student cannot access a certificate', function (): void {
        $student = j0m04eRole('student');
        $certificate = j0m04eCertificate();
        $this->actingAs($student);

        expect(Gate::allows('view', $certificate))->toBeFalse();
        $this->get(route('certificates.download', $certificate))->assertForbidden();
    });

    test('J0M04-FR-CERT-019: teachers cannot create, revoke, or download certificates', function (): void {
        $teacher = j0m04eRole('teacher');
        $certificate = j0m04eCertificate();
        $this->actingAs($teacher);

        expect(Gate::allows('create', Certificate::class))->toBeFalse()
            ->and(Gate::allows('revoke', $certificate))->toBeFalse();
        $this->get(route('certificates.download', $certificate))->assertForbidden();
    });

    test('J0M04-FR-CERT-019: anonymous download requests are rejected by auth middleware', function (): void {
        $certificate = Certificate::factory()->create();

        $this->get(route('certificates.download', $certificate))->assertRedirect();
    });

    test('J0M04-UC-CERT-004, J0M04-FR-CERT-016: admin download lazily stores the PDF', function (): void {
        Storage::fake('local');
        $admin = j0m04eRole('admin');
        $certificate = j0m04eCertificate();
        $this->actingAs($admin);

        $response = $this->get(route('certificates.download', $certificate));

        $response->assertDownload('certificate_'.$certificate->certificate_number.'.pdf');
        Storage::disk('local')->assertExists('certificates/certificate_'.$certificate->certificate_number.'.pdf');
    });

    test('J0M04-UC-CERT-005, J0M04-FR-CERT-016: owner download serves an already stored PDF', function (): void {
        Storage::fake('local');
        $student = j0m04eRole('student');
        $certificate = j0m04eOwnedCertificate($student);
        $path = 'certificates/certificate_'.$certificate->certificate_number.'.pdf';
        Storage::disk('local')->put($path, '%PDF-existing');
        $this->actingAs($student);

        $this->get(route('certificates.download', $certificate))
            ->assertDownload('certificate_'.$certificate->certificate_number.'.pdf');
    });

    test('J0M04-FR-CERT-020: revoked records remain downloadable by an authorized admin', function (): void {
        Storage::fake('local');
        $admin = j0m04eRole('admin');
        $this->actingAs($admin);
        $certificate = j0m04eCertificate();
        app(RevokeCertificateAction::class)->execute($certificate);

        $this->get(route('certificates.download', $certificate->fresh()))->assertOk();
        Storage::disk('local')->assertExists('certificates/certificate_'.$certificate->certificate_number.'.pdf');
    });

    test('J0M04-FR-CERT-019: list headers expose serial, student, status, and issue date', function (): void {
        $component = Livewire::actingAs(j0m04eRole('admin'))->test(CertificateList::class);
        $indexes = collect($component->instance()->headers())->pluck('index')->all();

        expect($indexes)->toContain('certificate_number', 'student_name', 'status', 'issued_at');
    });

    test('J0M04-FR-CERT-004: list computed templates excludes inactive rows', function (): void {
        $active = CertificateTemplate::factory()->create(['name' => 'Visible layout', 'is_active' => true]);
        CertificateTemplate::factory()->inactive()->create(['name' => 'Retired layout']);

        $component = Livewire::actingAs(j0m04eRole('admin'))->test(CertificateList::class);
        $names = collect($component->instance()->templates())->pluck('name')->all();

        expect($names)->toContain($active->name)->not->toContain('Retired layout');
    });

    test('J0M04-FR-CERT-004: list computed registrations only includes active records', function (): void {
        $active = Registration::factory()->create(['status' => 'active']);
        Registration::factory()->create(['status' => 'completed']);

        $component = Livewire::actingAs(j0m04eRole('admin'))->test(CertificateList::class);
        $ids = collect($component->instance()->activeRegistrations())->pluck('id')->all();

        expect($ids)->toContain($active->id)->toHaveCount(1);
    });

    test('J0M04-FR-CERT-012: batch selection excludes registrations with certificates', function (): void {
        Queue::fake();
        $admin = j0m04eRole('admin');
        $issued = Registration::factory()->create(['status' => 'active']);
        j0m04eCertificate(['registration_id' => $issued->id]);
        $available = Registration::factory()->create(['status' => 'active']);
        $template = CertificateTemplate::factory()->create();

        Livewire::actingAs($admin)->test(CertificateList::class)
            ->set('batchIssueTemplateId', $template->id)
            ->call('saveBatchIssue');

        Queue::assertPushed(BatchIssueCertificatesJob::class, function (BatchIssueCertificatesJob $job) use ($available, $issued): bool {
            return $job->registrationIds === [$available->id]
                && ! in_array($issued->id, $job->registrationIds, true);
        });
    });

    test('J0M04-FR-CERT-021: template manager defaults to portrait and active', function (): void {
        $component = Livewire::actingAs(j0m04eRole('admin'))->test(CertificateTemplateManager::class);

        $component->call('create');

        expect($component->get('formData.layout'))->toBe('portrait')
            ->and($component->get('formData.is_active'))->toBeTrue()
            ->and($component->get('showModal'))->toBeTrue();
    });

    test('J0M04-FR-CERT-001: template manager saves a valid template and closes the modal', function (): void {
        $component = Livewire::actingAs(j0m04eRole('admin'))->test(CertificateTemplateManager::class)
            ->call('create')
            ->set('formData.name', 'Manager template')
            ->set('formData.content_template', '<p>Issued</p>')
            ->call('saveTemplate');

        $component->assertHasNoErrors();
        expect($component->get('showModal'))->toBeFalse();
        $this->assertDatabaseHas('certificate_templates', ['name' => 'Manager template']);
    });

    test('J0M04-FR-CERT-017: confirmAction clears its target after revocation', function (): void {
        $admin = j0m04eRole('admin');
        $certificate = Certificate::factory()->create();

        $component = Livewire::actingAs($admin)->test(CertificateList::class)
            ->set('confirmTarget', $certificate->id)
            ->call('confirmAction');

        expect($component->get('confirmTarget'))->toBeNull()
            ->and($certificate->fresh()->status->value)->toBe('revoked');
    });

    test('J0M04-FR-CERT-019: student route renders the ownership-scoped page', function (): void {
        $student = j0m04eRole('student');

        $this->actingAs($student)->get(route('student.certificates'))
            ->assertOk();
    });
});
