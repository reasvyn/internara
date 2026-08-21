<?php

declare(strict_types=1);

use App\Certification\Certificate\Actions\IssueCertificateAction;
use App\Certification\Certificate\Models\Certificate;
use App\Certification\Certificate\Models\CertificateTemplate;
use App\Certification\Jobs\BatchIssueCertificatesJob;
use App\Enrollment\Registration\Models\Registration;
use App\Program\Internship\Models\Internship;
use App\User\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
});

test('8FVZA-FR-JOB1/JOB2/JOB3: BatchIssueCertificatesJob is a queued job with tries and backoff [2, 10, 30]', function () {
    $job = new BatchIssueCertificatesJob(
        registrationIds: ['reg-a'],
        status: 'active',
        templateId: 'template-id',
        issuedBy: 'admin-id',
    );

    expect($job)->toBeInstanceOf(ShouldQueue::class)
        ->and($job->tries)->toBe(3)
        ->and($job->backoff)->toBe([2, 10, 30]);
});

test('8FVZA-FR-JOB5: job references models by ID, not serialized models', function () {
    $registration = Registration::factory()->create(['status' => 'active']);
    $template = CertificateTemplate::factory()->create();

    $job = new BatchIssueCertificatesJob(
        registrationIds: [$registration->id],
        status: 'active',
        templateId: $template->id,
        issuedBy: 'admin-id',
    );

    $serialized = serialize($job);

    expect($serialized)->toContain($registration->id)
        ->and($serialized)->toContain($template->id)
        ->and($serialized)->toContain('admin-id')
        ->and($serialized)->not->toContain('App\\User\\Models\\User')
        ->and($serialized)->not->toContain('App\\Enrollment\\Registration\\Models\\Registration');
});

test('J0M04-FR-CI1/CI5/CI7: handle issues a certificate for each eligible registration', function () {
    $admin = User::factory()->create();
    $this->actingAs($admin);

    $template = CertificateTemplate::factory()->create(['is_active' => true]);
    $internship = Internship::factory()->create(['name' => 'PT Maju Bersama']);
    $registration1 = Registration::factory()->create([
        'internship_id' => $internship->id,
        'status' => 'active',
    ]);
    $registration2 = Registration::factory()->create([
        'internship_id' => $internship->id,
        'status' => 'active',
    ]);

    $job = new BatchIssueCertificatesJob(
        registrationIds: [$registration1->id, $registration2->id],
        status: 'active',
        templateId: $template->id,
        issuedBy: $admin->id,
    );

    $job->handle(app(IssueCertificateAction::class));

    $certs = Certificate::whereIn('registration_id', [$registration1->id, $registration2->id])->get();
    expect($certs)->toHaveCount(2)
        ->and($certs->pluck('template_content')->unique()->all())->toBe([$template->content_template])
        ->and($certs->firstWhere('registration_id', $registration1->id)->issued_at)->not->toBeNull();
});

test('8FVZA-UC-1: request for a registration without matching status produces no certificate', function () {
    $admin = User::factory()->create();
    $this->actingAs($admin);

    $template = CertificateTemplate::factory()->create(['is_active' => true]);

    $job = new BatchIssueCertificatesJob(
        registrationIds: ['missing-registration'],
        status: 'active',
        templateId: $template->id,
        issuedBy: $admin->id,
    );

    $job->handle(app(IssueCertificateAction::class));

    expect(Certificate::count())->toBe(0);
});

test('8FVZA-UC-1: registration with existing certificate is skipped (cert-free filter)', function () {
    $admin = User::factory()->create();
    $this->actingAs($admin);

    $template = CertificateTemplate::factory()->create(['is_active' => true]);
    $internship = Internship::factory()->create(['name' => 'PT Maju Bersama']);
    $registration = Registration::factory()->create([
        'internship_id' => $internship->id,
        'status' => 'active',
    ]);
    Certificate::factory()->create(['registration_id' => $registration->id]);

    $job = new BatchIssueCertificatesJob(
        registrationIds: [$registration->id],
        status: 'active',
        templateId: $template->id,
        issuedBy: $admin->id,
    );

    $job->handle(app(IssueCertificateAction::class));

    expect(Certificate::count())->toBe(1);
});

test('8FVZA-UC-1: registration with different status is skipped (status filter)', function () {
    $admin = User::factory()->create();
    $this->actingAs($admin);

    $template = CertificateTemplate::factory()->create(['is_active' => true]);
    $internship = Internship::factory()->create(['name' => 'PT Maju Bersama']);
    $registration = Registration::factory()->create([
        'internship_id' => $internship->id,
        'status' => 'completed',
    ]);

    $job = new BatchIssueCertificatesJob(
        registrationIds: [$registration->id],
        status: 'active',
        templateId: $template->id,
        issuedBy: $admin->id,
    );

    $job->handle(app(IssueCertificateAction::class));

    expect(Certificate::count())->toBe(0);
});

test('8FVZA-FR-JOB2/JOB3: missing template throws during handle and failed() logs with context', function () {
    $logs = captureLogs();

    $job = new BatchIssueCertificatesJob(
        registrationIds: ['reg-a'],
        status: 'active',
        templateId: 'missing-template-id',
        issuedBy: 'admin-id',
    );

    expect(fn () => $job->handle(app(IssueCertificateAction::class)))
        ->toThrow(ModelNotFoundException::class);

    $job->failed(new RuntimeException('template not found'));

    $log = $logs->last();
    expect($log->level)->toBe('error')
        ->and($log->message)->toBe('Batch certificate issuance failed')
        ->and($log->context['template_id'])->toBe('missing-template-id')
        ->and($log->context['status'])->toBe('active')
        ->and($log->context['registration_ids'])->toBe(['reg-a']);
});

test('8FVZA-FR-JOB5: dispatch pushes the job to the queue with minimal payload', function () {
    BatchIssueCertificatesJob::dispatch(
        registrationIds: ['reg-a'],
        status: 'active',
        templateId: 'template-id',
        issuedBy: 'admin-id',
    );

    Queue::assertPushed(BatchIssueCertificatesJob::class);
});
