<?php

declare(strict_types=1);

use App\Certification\Actions\DispatchBatchIssueCertificatesAction;
use App\Certification\Certificate\Models\CertificateTemplate;
use App\Certification\Data\BatchIssueCertificatesData;
use App\Certification\Jobs\BatchIssueCertificatesJob;
use App\Enrollment\Registration\Models\Registration;
use App\Program\Internship\Models\Internship;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Activitylog\Models\Activity;

uses(LazilyRefreshDatabase::class);

test('8FVZA-UC-1/UC-2 + J0M04-FR-BP2: DispatchBatchIssueCertificatesAction dispatches BatchIssueCertificatesJob on default queue with cohort parameters', function () {
    Queue::fake();

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

    $dispatchAction = app(DispatchBatchIssueCertificatesAction::class);

    $dispatchAction->execute(new BatchIssueCertificatesData(
        registrationIds: [$registration1->id, $registration2->id],
        status: 'active',
        templateId: $template->id,
    ));

    Queue::assertPushed(
        BatchIssueCertificatesJob::class,
        function ($job) use ($registration1, $registration2, $template, $admin) {
            return $job->registrationIds === [$registration1->id, $registration2->id]
                && $job->status === 'active'
                && $job->templateId === $template->id
                && $job->issuedBy === $admin->id;
        },
    );

    Queue::assertPushedOn('default', BatchIssueCertificatesJob::class);
});

test('8FVZA-UC-2: DispatchBatchIssueCertificatesAction logs dispatch event', function () {
    Queue::fake();

    $admin = User::factory()->create();
    $this->actingAs($admin);

    $template = CertificateTemplate::factory()->create(['is_active' => true]);
    $registration = Registration::factory()->create(['status' => 'active']);

    $dispatchAction = app(DispatchBatchIssueCertificatesAction::class);

    $dispatchAction->execute(new BatchIssueCertificatesData(
        registrationIds: [$registration->id],
        status: 'active',
        templateId: $template->id,
    ));

    // Activity log entry created via BaseAction::log()
    $log = Activity::where('event', 'certificate_batch_queued')->first();
    expect($log)->not->toBeNull()
        ->and($log->event)->toBe('certificate_batch_queued');
});

test('8FVZA-UC-1: DispatchBatchIssueCertificatesAction with empty cohort dispatches job with empty ids (no-op in job)', function () {
    Queue::fake();

    $admin = User::factory()->create();
    $this->actingAs($admin);

    $template = CertificateTemplate::factory()->create(['is_active' => true]);

    $dispatchAction = app(DispatchBatchIssueCertificatesAction::class);

    // Action dispatches job even with empty array - job handles empty gracefully
    $dispatchAction->execute(new BatchIssueCertificatesData(
        registrationIds: [],
        status: 'active',
        templateId: $template->id,
    ));

    Queue::assertPushed(BatchIssueCertificatesJob::class, function ($job) {
        return $job->registrationIds === [];
    });
});
