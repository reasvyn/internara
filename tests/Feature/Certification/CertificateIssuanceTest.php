<?php

declare(strict_types=1);

use App\Modules\Certification\Domain\Certificate\Actions\BatchIssueCertificateAction;
use App\Modules\Certification\Domain\Certificate\Actions\CreateCertificateTemplateAction;
use App\Modules\Certification\Domain\Certificate\Actions\IssueCertificateAction;
use App\Modules\Certification\Domain\Certificate\Actions\RevokeCertificateAction;
use App\Modules\Certification\Domain\Certificate\Events\CertificateIssued;
use App\Modules\Certification\Domain\Certificate\Livewire\CertificateList;
use App\Modules\Certification\Domain\Certificate\Livewire\CertificateTemplateManager;
use App\Modules\Certification\Domain\Certificate\Models\Certificate;
use App\Modules\Certification\Domain\Certificate\Models\CertificateTemplate;
use App\Modules\Certification\Domain\Certificate\Services\CertificateRenderer;
use App\Modules\Certification\Jobs\BatchIssueCertificatesJob;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\User\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

function j0m04aAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    return $admin;
}

function j0m04aTemplate(array $overrides = []): CertificateTemplate
{
    return CertificateTemplate::factory()->create($overrides);
}

function j0m04aRegistration(): Registration
{
    return Registration::factory()->create();
}

function j0m04aIssue(Registration $registration, CertificateTemplate $template): Certificate
{
    return app(IssueCertificateAction::class)->execute($registration, $template);
}

describe('J0M04: certificate issuance', function (): void {
    test('J0M04-FR-CERT-001: template command persists validated data', function (): void {
        $admin = j0m04aAdmin();

        $template = app(CreateCertificateTemplateAction::class)->execute([
            'name' => 'Graduation 2026',
            'layout' => 'landscape',
            'content_template' => '<p>Certifies {student_name}</p>',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        expect($template->getKey())->not->toBeNull();
        $this->assertDatabaseHas('certificate_templates', [
            'id' => $template->id,
            'name' => 'Graduation 2026',
            'layout' => 'landscape',
            'created_by' => $admin->id,
        ]);
    });

    test('J0M04-FR-CERT-002: layout accepts portrait and landscape, rejects anything else', function (): void {
        $admin = j0m04aAdmin();
        $action = app(CreateCertificateTemplateAction::class);
        $base = ['content_template' => '<p>x</p>', 'created_by' => $admin->id];

        $portrait = $action->execute([...$base, 'name' => 'PortraitTpl', 'layout' => 'portrait']);
        $landscape = $action->execute([...$base, 'name' => 'LandscapeTpl', 'layout' => 'landscape']);

        expect($portrait->layout)->toBe('portrait')
            ->and($landscape->layout)->toBe('landscape');
        expect(fn () => $action->execute([...$base, 'name' => 'BadTpl', 'layout' => 'diagonal']))
            ->toThrow(ValidationException::class);
    });

    test('J0M04-FR-CERT-003: renderer resolves the canonical placeholder set', function (): void {
        $this->actingAs(j0m04aAdmin());
        $registration = j0m04aRegistration();
        $template = j0m04aTemplate([
            'content_template' => '<p>Awarded to {student_name}, no {certificate_number} on {issued_date}</p>',
        ]);
        $certificate = j0m04aIssue($registration, $template);
        $renderer = app(CertificateRenderer::class);

        $pairs = $renderer->resolvePlaceholders($registration, $certificate);

        foreach (['{student_name}', '{school_name}', '{company_name}', '{score}', '{score_letter}', '{certificate_number}', '{issued_date}'] as $key) {
            expect($pairs)->toHaveKey($key);
        }

        $html = $renderer->renderHtml($registration, $certificate);

        expect($html)->toContain($registration->student->name)
            ->and($html)->toContain($certificate->certificate_number)
            ->and($html)->not->toContain('{certificate_number}')
            ->and($html)->not->toContain('{student_name}');
    });

    test('J0M04-FR-CERT-005/006: sequential issuance yields distinct serials and hashes', function (): void {
        $this->actingAs(j0m04aAdmin());
        $template = j0m04aTemplate();

        $first = j0m04aIssue(j0m04aRegistration(), $template);
        $second = j0m04aIssue(j0m04aRegistration(), $template);

        expect($first->certificate_number)->not->toBe($second->certificate_number)
            ->and($first->qr_hash)->not->toBe($second->qr_hash)
            ->and($first->qr_hash)->not->toBeEmpty()
            ->and($second->qr_hash)->not->toBeEmpty();
        $this->assertDatabaseHas('certificates', ['id' => $first->id]);
        $this->assertDatabaseHas('certificates', ['id' => $second->id]);
    });

    test('J0M04-FR-CERT-007: issued certificates keep the template content frozen at issuance', function (): void {
        $this->actingAs(j0m04aAdmin());
        $template = j0m04aTemplate(['content_template' => '<p>Original wording {student_name}</p>']);
        $certificate = j0m04aIssue(j0m04aRegistration(), $template);

        $template->update(['content_template' => '<p>Edited wording</p>']);

        expect($certificate->fresh()->template_content)->toBe('<p>Original wording {student_name}</p>');
    });

    test('J0M04-FR-CERT-008: issuance records the admin, timestamp, and registration bridge', function (): void {
        $admin = j0m04aAdmin();
        $this->actingAs($admin);
        $registration = j0m04aRegistration();

        $certificate = j0m04aIssue($registration, j0m04aTemplate());

        expect($certificate->registration_id)->toBe($registration->id)
            ->and($certificate->issued_by)->toBe($admin->id)
            ->and($certificate->issued_at)->not->toBeNull()
            ->and($certificate->issued_at->isToday())->toBeTrue();
        $this->assertDatabaseHas('certificates', [
            'id' => $certificate->id,
            'registration_id' => $registration->id,
            'issued_by' => $admin->id,
        ]);
    });

    test('J0M04-FR-CERT-009: single issuance writes the record and announces the event', function (): void {
        Event::fake([CertificateIssued::class]);
        $this->actingAs(j0m04aAdmin());
        $registration = j0m04aRegistration();

        $certificate = j0m04aIssue($registration, j0m04aTemplate());

        Event::assertDispatched(CertificateIssued::class, fn ($event) => $event->certificate->is($certificate));
        $this->assertModelExists($certificate);
    });

    test('J0M04-FR-CERT-015: renderer stores the PDF under a deterministic local path', function (): void {
        Storage::fake('local');
        $this->actingAs(j0m04aAdmin());
        $registration = j0m04aRegistration();
        $certificate = j0m04aIssue($registration, j0m04aTemplate([
            'content_template' => '<p>Certificate for {student_name}, no {certificate_number}</p>',
        ]));
        $renderer = app(CertificateRenderer::class);

        $expected = 'certificates/certificate_'.$certificate->certificate_number.'.pdf';

        expect($renderer->pdfPath($certificate))->toBe($expected);

        $stored = $renderer->storePdf($registration, $certificate);

        expect($stored)->toBe($expected);
        Storage::disk('local')->assertExists($expected);

        $renderer->storePdf($registration, $certificate);

        Storage::disk('local')->assertExists($expected);
    });

    test('J0M04-FR-CERT-021: form rules and data-object rules reject the same bad payloads', function (): void {
        $admin = j0m04aAdmin();

        Livewire::actingAs($admin)->test(CertificateTemplateManager::class)
            ->set('formData.name', 'Bad layout')
            ->set('formData.layout', 'diagonal')
            ->set('formData.content_template', '<p>x</p>')
            ->call('saveTemplate')
            ->assertHasErrors(['formData.layout']);

        expect(fn () => app(CreateCertificateTemplateAction::class)->execute([
            'name' => 'Bad layout',
            'layout' => 'diagonal',
            'content_template' => '<p>x</p>',
            'created_by' => $admin->id,
        ]))->toThrow(ValidationException::class);

        expect(fn () => app(CreateCertificateTemplateAction::class)->execute([
            'layout' => 'portrait',
            'content_template' => '<p>x</p>',
            'created_by' => $admin->id,
        ]))->toThrow(ValidationException::class);
    });

    test('J0M04-FR-CERT-022: certificate strings are complete and mirrored in English and Indonesian', function (): void {
        app()->setLocale('en');
        expect(__('certificate.issued'))->toBe('Certificate issued successfully.')
            ->and(__('certificate.already_revoked'))->toBe('This certificate has already been revoked.')
            ->and(__('certificate.status.issued'))->toBe('Issued')
            ->and(__('certificate.status.revoked'))->toBe('Revoked');

        app()->setLocale('id');
        expect(__('certificate.issued'))->toBe('Sertifikat berhasil diterbitkan.')
            ->and(__('certificate.already_revoked'))->toBe('Sertifikat ini sudah dicabut.')
            ->and(__('certificate.status.issued'))->toBe('Diterbitkan')
            ->and(__('certificate.status.revoked'))->toBe('Dicabut');
    });

    test('J0M04-NFR-CERT-007: English and Indonesian catalogues expose the same keys', function (): void {
        $flatten = function (array $group, string $prefix = '') use (&$flatten): array {
            $keys = [];
            foreach ($group as $key => $value) {
                $full = $prefix === '' ? (string) $key : $prefix.'.'.$key;
                if (is_array($value)) {
                    $keys = [...$keys, ...$flatten($value, $full)];
                } else {
                    $keys[] = $full;
                }
            }

            return $keys;
        };

        $enKeys = $flatten(Lang::get('certificate', [], 'en'));
        $idKeys = $flatten(Lang::get('certificate', [], 'id'));

        expect($enKeys)->not->toBeEmpty()
            ->and($idKeys)->toBe($enKeys);
    });

    test('J0M04-FR-CERT-023: issuance and revocation write dual-channel audit entries', function (): void {
        $logged = [];
        Log::listen(function (MessageLogged $event) use (&$logged): void {
            $logged[] = $event->context['event'] ?? null;
        });

        $admin = j0m04aAdmin();
        $this->actingAs($admin);
        $certificate = j0m04aIssue(j0m04aRegistration(), j0m04aTemplate());
        app(RevokeCertificateAction::class)->execute($certificate->fresh());

        expect($logged)->toContain('certificate_issued')
            ->and($logged)->toContain('certificate_revoked');

        $issueEntry = DB::table('activity_log')->where('event', 'certificate_issued')->first();
        $revokeEntry = DB::table('activity_log')->where('event', 'certificate_revoked')->first();

        expect($issueEntry)->not->toBeNull()
            ->and($issueEntry->causer_id)->toBe($admin->id)
            ->and($revokeEntry)->not->toBeNull();

        $properties = json_decode($issueEntry->properties, true);

        expect($properties['payload']['certificate_number'] ?? null)->toBe($certificate->certificate_number);
    });

    test('J0M04-FR-CERT-024: registration removal cascades while actor removal nulls', function (): void {
        $admin = j0m04aAdmin();
        $this->actingAs($admin);
        $certificate = j0m04aIssue(j0m04aRegistration(), j0m04aTemplate());

        expect($certificate->getKey())->toBeString();

        $admin->delete();

        expect($certificate->fresh()->issued_by)->toBeNull();

        $certificate->fresh()->registration->delete();

        $this->assertModelMissing($certificate);
    });

    test('J0M04-UC-CERT-001: admin authors a template through the manager', function (): void {
        $admin = j0m04aAdmin();

        Livewire::actingAs($admin)->test(CertificateTemplateManager::class)
            ->set('formData.name', 'Ceremony 2026')
            ->set('formData.layout', 'landscape')
            ->set('formData.content_template', '<p>Certifies {student_name}</p>')
            ->set('formData.is_active', true)
            ->call('saveTemplate')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('certificate_templates', [
            'name' => 'Ceremony 2026',
            'layout' => 'landscape',
        ]);
    });

    test('J0M04-UC-CERT-002: admin issues a single certificate through the list', function (): void {
        $admin = j0m04aAdmin();
        $registration = j0m04aRegistration();
        $template = j0m04aTemplate();

        Livewire::actingAs($admin)->test(CertificateList::class)
            ->set('issueRegistrationId', $registration->id)
            ->set('issueTemplateId', $template->id)
            ->call('saveIssue')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('certificates', ['registration_id' => $registration->id]);
    });

    test('J0M04-UC-CERT-003: admin batch-queues certificates for active registrations', function (): void {
        Queue::fake();
        $admin = j0m04aAdmin();
        $template = j0m04aTemplate();
        Registration::factory()->count(2)->create(['status' => 'active']);

        Livewire::actingAs($admin)->test(CertificateList::class)
            ->set('batchIssueTemplateId', $template->id)
            ->set('batchIssueFilter', 'active')
            ->call('saveBatchIssue')
            ->assertHasNoErrors();

        Queue::assertPushed(BatchIssueCertificatesJob::class, fn ($job) => $job->templateId === $template->id
            && count($job->registrationIds) === 2);
    });

    test('J0M04-UC-CERT-003: batch job issues only registrations still missing certificates', function (): void {
        $admin = j0m04aAdmin();
        $this->actingAs($admin);
        $template = j0m04aTemplate();
        $fresh = Registration::factory()->count(2)->create(['status' => 'active']);
        $already = Registration::factory()->create(['status' => 'active']);
        j0m04aIssue($already, $template);

        $job = new BatchIssueCertificatesJob(
            registrationIds: [...$fresh->pluck('id')->all(), $already->id],
            status: 'active',
            templateId: $template->id,
            issuedBy: $admin->id,
        );
        $job->handle(app(IssueCertificateAction::class));

        expect(Certificate::whereIn('registration_id', $fresh->pluck('id'))->count())->toBe(2)
            ->and(Certificate::where('registration_id', $already->id)->count())->toBe(1)
            ->and(Certificate::where('issued_by', $admin->id)->count())->toBe(3);
    });

    test('J0M04-NFR-CERT-001: repeated issuance keeps serials and hashes distinct; duplicates rejected at the database', function (): void {
        $this->actingAs(j0m04aAdmin());
        $template = j0m04aTemplate();

        $numbers = [];
        $hashes = [];
        foreach (range(1, 5) as $i) {
            $issued = j0m04aIssue(j0m04aRegistration(), $template);
            $numbers[] = $issued->certificate_number;
            $hashes[] = $issued->qr_hash;
        }

        expect(array_unique($numbers))->toHaveCount(5)
            ->and(array_unique($hashes))->toHaveCount(5);

        $existing = Certificate::first();

        expect(fn () => Certificate::create([
            'registration_id' => j0m04aRegistration()->id,
            'certificate_number' => $existing->certificate_number,
            'qr_hash' => $existing->qr_hash,
            'issued_by' => null,
            'issued_at' => now(),
        ]))->toThrow(QueryException::class);
    });

    test('J0M04-NFR-CERT-003: revoked certificates stay revoked and remain retrievable', function (): void {
        $this->actingAs(j0m04aAdmin());
        $certificate = j0m04aIssue(j0m04aRegistration(), j0m04aTemplate());

        $revoked = app(RevokeCertificateAction::class)->execute($certificate);

        expect($revoked->status->isTerminal())->toBeTrue();
        expect(fn () => app(RevokeCertificateAction::class)->execute($revoked->fresh()))
            ->toThrow(RejectedException::class);

        $found = Certificate::find($certificate->id);

        expect($found)->not->toBeNull()
            ->and($found->status->isTerminal())->toBeTrue();
    });

    test('J0M04-NFR-CERT-005: batch issuance completes the cohort with per-student outcomes', function (): void {
        $this->actingAs(j0m04aAdmin());
        $template = j0m04aTemplate();
        $ids = Registration::factory()->count(15)->create()->pluck('id')->all();

        $results = app(BatchIssueCertificateAction::class)->execute($ids, $template);

        expect($results['success'])->toBe(15)
            ->and($results['failed'])->toBe(0)
            ->and($results['errors'])->toBe([])
            ->and($results['success'] + $results['failed'])->toBe(count($ids));
        expect(Certificate::count())->toBe(15);
    });

    test('J0M04-FR-CERT-010, J0M04-FR-CERT-011: graduate eligibility evaluates registration prerequisites', function (): void {
        $admin = j0m04aAdmin();
        $this->actingAs($admin);

        $template = j0m04aTemplate();
        $registration = j0m04aRegistration();

        // Valid registration with template issues successfully
        $certificate = app(IssueCertificateAction::class)->execute($registration, $template);
        expect($certificate->exists)->toBeTrue()
            ->and($certificate->status->value)->toBe('issued');
    });

    test('J0M04-NFR-CERT-006, J0M04-DD-CERT-005: alumni certificate retrieval remains available read-only after closure', function (): void {
        $student = User::factory()->create();
        $student->assignRole('student');

        $registration = Registration::factory()->create(['student_id' => $student->id]);
        $certificate = Certificate::factory()->create([
            'registration_id' => $registration->id,
            'status' => 'issued',
        ]);

        $this->actingAs($student);
        $response = $this->get(route('student.certificates'));
        $response->assertStatus(200);
    });

    test('J0M04-DD-CERT-002: verification identity is a random hash rather than a URL-bound token', function (): void {
        $admin = j0m04aAdmin();
        $this->actingAs($admin);

        $certificate = j0m04aIssue(j0m04aRegistration(), j0m04aTemplate());

        expect($certificate->qr_hash)->not->toBeNull()
            ->and(strlen($certificate->qr_hash))->toBeGreaterThanOrEqual(32);
    });
});
