<?php

declare(strict_types=1);

use App\Modules\Certification\Actions\DispatchBatchIssueCertificatesAction;
use App\Modules\Certification\Data\BatchIssueCertificatesData;
use App\Modules\Certification\Domain\Certificate\Actions\BatchIssueCertificateAction;
use App\Modules\Certification\Domain\Certificate\Actions\CreateCertificateTemplateAction;
use App\Modules\Certification\Domain\Certificate\Actions\IssueCertificateAction;
use App\Modules\Certification\Domain\Certificate\Actions\RevokeCertificateAction;
use App\Modules\Certification\Domain\Certificate\Enums\CertificateStatus;
use App\Modules\Certification\Domain\Certificate\Events\CertificateIssued;
use App\Modules\Certification\Domain\Certificate\Livewire\CertificateList;
use App\Modules\Certification\Domain\Certificate\Livewire\StudentCertificates;
use App\Modules\Certification\Domain\Certificate\Models\Certificate;
use App\Modules\Certification\Domain\Certificate\Models\CertificateTemplate;
use App\Modules\Certification\Domain\Certificate\Services\CertificateRenderer;
use App\Modules\Certification\Jobs\BatchIssueCertificatesJob;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroupMember;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

function j0m04cAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

function j0m04cStudent(): User
{
    $user = User::factory()->create();
    $user->assignRole('student');

    return $user;
}

function j0m04cOwnedRegistration(User $student): Registration
{
    $registration = Registration::factory()->create();
    InternshipGroupMember::factory()->create([
        'registration_id' => $registration->id,
        'user_id' => $student->id,
    ]);

    return $registration;
}

function j0m04cTemplate(array $overrides = []): CertificateTemplate
{
    return CertificateTemplate::factory()->create($overrides);
}

function j0m04cIssue(Registration $registration, CertificateTemplate $template): Certificate
{
    return app(IssueCertificateAction::class)->execute($registration, $template);
}

describe('J0M04: certification implementation behavior', function (): void {
    test('J0M04-FR-CERT-001: template action records the creator and active flag', function (): void {
        $admin = j0m04cAdmin();

        $template = app(CreateCertificateTemplateAction::class)->execute([
            'name' => 'Retired 2026',
            'layout' => 'portrait',
            'content_template' => '<p>{student_name}</p>',
            'is_active' => false,
            'created_by' => $admin->id,
        ]);

        expect($template->is_active)->toBeFalse()
            ->and($template->createdBy->is($admin))->toBeTrue();
    });

    test('J0M04-FR-CERT-001: template action rejects an overlong name', function (): void {
        expect(fn () => app(CreateCertificateTemplateAction::class)->execute([
            'name' => str_repeat('x', 256),
            'layout' => 'portrait',
            'content_template' => '<p>x</p>',
            'created_by' => j0m04cAdmin()->id,
        ]))->toThrow(ValidationException::class);
    });

    test('J0M04-FR-CERT-003: template action requires content', function (): void {
        expect(fn () => app(CreateCertificateTemplateAction::class)->execute([
            'name' => 'Incomplete',
            'layout' => 'portrait',
            'created_by' => j0m04cAdmin()->id,
        ]))->toThrow(ValidationException::class);
    });

    test('J0M04-FR-CERT-003: template action rejects an unknown creator', function (): void {
        expect(fn () => app(CreateCertificateTemplateAction::class)->execute([
            'name' => 'Unknown creator',
            'layout' => 'portrait',
            'content_template' => '<p>x</p>',
            'created_by' => '00000000-0000-0000-0000-000000000000',
        ]))->toThrow(ValidationException::class);
    });

    test('J0M04-FR-CERT-005: issuance numbers contain a year and padded sequence', function (): void {
        $this->actingAs(j0m04cAdmin());

        $certificate = j0m04cIssue(Registration::factory()->create(), j0m04cTemplate());

        expect($certificate->certificate_number)->toMatch('/^.+\/'.now()->year.'\/\d{4}$/');
    });

    test('J0M04-FR-CERT-005: issuance generates a 64-character verification hash', function (): void {
        $this->actingAs(j0m04cAdmin());

        $certificate = j0m04cIssue(Registration::factory()->create(), j0m04cTemplate());

        expect($certificate->qr_hash)->toMatch('/^[a-f0-9]{64}$/');
    });

    test('J0M04-FR-CERT-007: an empty template snapshot is preserved at issuance', function (): void {
        $this->actingAs(j0m04cAdmin());
        $template = j0m04cTemplate(['content_template' => '']);

        $certificate = j0m04cIssue(Registration::factory()->create(), $template);

        expect($certificate->template_content)->toBe('');
    });

    test('J0M04-FR-CERT-008: an unauthenticated issuance leaves the issuer nullable', function (): void {
        $certificate = j0m04cIssue(Registration::factory()->create(), j0m04cTemplate());

        expect($certificate->issued_by)->toBeNull()
            ->and($certificate->issuer)->toBeNull();
    });

    test('J0M04-FR-CERT-009: issuance dispatches after the record is persisted', function (): void {
        Event::fake();
        $this->actingAs(j0m04cAdmin());

        $certificate = j0m04cIssue(Registration::factory()->create(), j0m04cTemplate());

        Event::assertDispatched(function (CertificateIssued $event) use ($certificate): bool {
            return $event->certificate->exists && $event->certificate->is($certificate);
        });
    });

    test('J0M04-FR-CERT-012: batch reports an unknown registration as failed', function (): void {
        $this->actingAs(j0m04cAdmin());
        $valid = Registration::factory()->create();
        $template = j0m04cTemplate();

        $result = app(BatchIssueCertificateAction::class)->execute([
            $valid->id,
            '00000000-0000-0000-0000-000000000000',
        ], $template);

        expect($result['success'])->toBe(1)
            ->and($result['failed'])->toBe(1)
            ->and($result['errors'])->toBe([])
            ->and(Certificate::where('registration_id', $valid->id)->exists())->toBeTrue();
    });

    test('J0M04-FR-CERT-013: an empty batch returns zero outcomes', function (): void {
        $result = app(BatchIssueCertificateAction::class)->execute([], j0m04cTemplate());

        expect($result)->toBe(['success' => 0, 'failed' => 0, 'errors' => []]);
    });

    test('J0M04-FR-CERT-014: dispatch action forwards every DTO field to the queue', function (): void {
        Queue::fake();
        $data = new BatchIssueCertificatesData(['r-1'], 'completed', 'tpl-1');
        $action = app(DispatchBatchIssueCertificatesAction::class);

        expect($action->execute($data))->toBeNull();
        Queue::assertPushed(BatchIssueCertificatesJob::class, fn (BatchIssueCertificatesJob $job): bool => $job->registrationIds === ['r-1']
            && $job->status === 'completed'
            && $job->templateId === 'tpl-1');
    });

    test('J0M04-FR-CERT-017: revocation records actor, timestamp, and terminal status', function (): void {
        $admin = j0m04cAdmin();
        $this->actingAs($admin);
        $certificate = j0m04cIssue(Registration::factory()->create(), j0m04cTemplate());

        $revoked = app(RevokeCertificateAction::class)->execute($certificate);

        expect($revoked->status->value)->toBe('revoked')
            ->and($revoked->revoked_by)->toBe($admin->id)
            ->and($revoked->revoked_at)->not->toBeNull();
    });

    test('J0M04-FR-CERT-018: a second revocation is rejected in Indonesian', function (): void {
        app()->setLocale('id');
        $this->actingAs(j0m04cAdmin());
        $certificate = j0m04cIssue(Registration::factory()->create(), j0m04cTemplate());
        $revoked = app(RevokeCertificateAction::class)->execute($certificate);

        expect(fn () => app(RevokeCertificateAction::class)->execute($revoked))
            ->toThrow(RejectedException::class, 'Sertifikat ini sudah dicabut.');
    });

    test('J0M04-FR-CERT-019: student certificate page only renders issued owned records', function (): void {
        $student = j0m04cStudent();
        $owned = j0m04cOwnedRegistration($student);
        $other = Registration::factory()->create();
        $issued = Certificate::factory()->create(['registration_id' => $owned->id, 'certificate_number' => 'OWNED-1']);
        Certificate::factory()->create(['registration_id' => $owned->id, 'status' => 'revoked', 'certificate_number' => 'OWNED-2']);
        Certificate::factory()->create(['registration_id' => $other->id, 'certificate_number' => 'OTHER-1']);

        Livewire::actingAs($student)->test(StudentCertificates::class)
            ->assertSee($issued->certificate_number)
            ->assertDontSee('OWNED-2')
            ->assertDontSee('OTHER-1');
    });

    test('J0M04-FR-CERT-019: non-students cannot mount the student certificate page', function (): void {
        Livewire::actingAs(j0m04cAdmin())->test(StudentCertificates::class)->assertForbidden();
    });

    test('J0M04-FR-CERT-020: student certificates remain queryable after a registration status change', function (): void {
        $student = j0m04cStudent();
        $registration = j0m04cOwnedRegistration($student);
        $registration->update(['status' => 'completed']);
        $certificate = Certificate::factory()->create(['registration_id' => $registration->id]);

        Livewire::actingAs($student)->test(StudentCertificates::class)
            ->assertSee($certificate->certificate_number);
    });

    test('J0M04-FR-CERT-021: list validation rejects missing issuance selections', function (): void {
        Livewire::actingAs(j0m04cAdmin())->test(CertificateList::class)
            ->call('saveIssue')
            ->assertHasErrors(['issueRegistrationId', 'issueTemplateId']);
    });

    test('J0M04-FR-CERT-021: list validation rejects an unknown template', function (): void {
        Livewire::actingAs(j0m04cAdmin())->test(CertificateList::class)
            ->set('issueRegistrationId', Registration::factory()->create()->id)
            ->set('issueTemplateId', '00000000-0000-0000-0000-000000000000')
            ->call('saveIssue')
            ->assertHasErrors(['issueTemplateId']);
    });

    test('J0M04-FR-CERT-023: template creation is logged with the created template', function (): void {
        $admin = j0m04cAdmin();
        $this->actingAs($admin);

        $template = app(CreateCertificateTemplateAction::class)->execute([
            'name' => 'Audited template',
            'layout' => 'portrait',
            'content_template' => '<p>x</p>',
            'created_by' => $admin->id,
        ]);

        expect(DB::table('activity_log')->where('event', 'certificate_template_created')
            ->where('subject_id', $template->id)->exists())->toBeTrue();
    });

    test('J0M04-FR-CERT-024: deleting a template does not delete its issued snapshot', function (): void {
        $this->actingAs(j0m04cAdmin());
        $template = j0m04cTemplate(['content_template' => '<p>Frozen</p>']);
        $certificate = j0m04cIssue(Registration::factory()->create(), $template);

        $template->delete();

        expect(Certificate::find($certificate->id)->template_content)->toBe('<p>Frozen</p>');
    });

    test('J0M04-FR-CERT-025: certificate model casts status and dates', function (): void {
        $certificate = Certificate::factory()->create(['revoked_at' => now()]);

        expect($certificate->status)->toBeInstanceOf(CertificateStatus::class)
            ->and($certificate->issued_at)->toBeInstanceOf(Carbon\Carbon::class)
            ->and($certificate->revoked_at)->toBeInstanceOf(Carbon\Carbon::class);
    });

    test('J0M04-NFR-CERT-002: batch skips missing registrations without creating a certificate', function (): void {
        $this->actingAs(j0m04cAdmin());
        $missing = '00000000-0000-0000-0000-000000000001';

        app(BatchIssueCertificateAction::class)->execute([$missing], j0m04cTemplate());

        expect(Certificate::count())->toBe(0);
    });

    test('J0M04-NFR-CERT-004: local certificate storage is outside the public disk', function (): void {
        $certificate = Certificate::factory()->create();
        Storage::fake('local');

        $path = app(CertificateRenderer::class)->pdfPath($certificate);

        expect($path)->toStartWith('certificates/')->and($path)->not->toContain('public/');
    });
});
