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
        studentIds: ['student-a'],
        templateId: 'template-id',
        issuedBy: 'admin-id',
    );

    expect($job)->toBeInstanceOf(ShouldQueue::class)
        ->and($job->tries)->toBe(3)
        ->and($job->backoff)->toBe([2, 10, 30]);
});

test('8FVZA-FR-JOB5: job references models by ID, not serialized models', function () {
    $student = User::factory()->create();
    $template = CertificateTemplate::factory()->create();

    $job = new BatchIssueCertificatesJob(
        studentIds: [$student->id],
        templateId: $template->id,
        issuedBy: 'admin-id',
    );

    $serialized = serialize($job);

    expect($serialized)->toContain($student->id)
        ->and($serialized)->toContain($template->id)
        ->and($serialized)->toContain('admin-id')
        ->and($serialized)->not->toContain('App\\User\\Models\\User');
});

test('J0M04-FR-CI1/CI5/CI7: handle issues a certificate for each registered student', function () {
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
        studentIds: [$registration1->student_id, $registration2->student_id],
        templateId: $template->id,
        issuedBy: $admin->id,
    );

    $job->handle(app(IssueCertificateAction::class));

    $certs = Certificate::whereIn('registration_id', [$registration1->id, $registration2->id])->get();
    expect($certs)->toHaveCount(2)
        ->and($certs->pluck('template_content')->unique()->all())->toBe([$template->content_template])
        ->and($certs->firstWhere('registration_id', $registration1->id)->issued_at)->not->toBeNull();
});

test('8FVZA-UC-1: request for a student without a registration produces no certificate', function () {
    $admin = User::factory()->create();
    $this->actingAs($admin);

    $template = CertificateTemplate::factory()->create(['is_active' => true]);

    $job = new BatchIssueCertificatesJob(
        studentIds: ['missing-student'],
        templateId: $template->id,
        issuedBy: $admin->id,
    );

    $job->handle(app(IssueCertificateAction::class));

    expect(Certificate::count())->toBe(0);
});

test('8FVZA-FR-JOB2/JOB3: missing template throws during handle and failed() logs with context', function () {
    $logs = captureLogs();

    $job = new BatchIssueCertificatesJob(
        studentIds: ['student-a'],
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
        ->and($log->context['student_ids'])->toBe(['student-a']);
});

test('8FVZA-FR-JOB5: dispatch pushes the job to the queue with minimal payload', function () {
    BatchIssueCertificatesJob::dispatch(
        studentIds: ['student-a'],
        templateId: 'template-id',
        issuedBy: 'admin-id',
    );

    Queue::assertPushed(BatchIssueCertificatesJob::class);
});
