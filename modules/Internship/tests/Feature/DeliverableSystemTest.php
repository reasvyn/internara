<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Internship\Livewire\DeliverableSubmission;
use Modules\Internship\Models\InternshipRegistration;
use Modules\Internship\Services\Contracts\DeliverableService;
use Modules\User\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    \Modules\Permission\Models\Role::create(['name' => 'student']);
    $this->user = User::factory()->create();
    $this->user->assignRole('student');
    $this->actingAs($this->user);

    // Create active registration
    $this->registration = InternshipRegistration::factory()->create([
        'student_id' => $this->user->id,
    ]);
    $this->registration->setStatus('active');
});

test('student can see deliverable submission page', function () {
    Livewire::test(DeliverableSubmission::class)->assertOk()->assertSee('Report');
});

test('student can upload a report', function () {
    $file = UploadedFile::fake()->create('report.pdf', 1000);

    Livewire::test(DeliverableSubmission::class)
        ->set('reportFile', $file)
        ->call('submitReport')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('internship_deliverables', [
        'registration_id' => $this->registration->id,
        'type' => 'report',
    ]);
});

test('deliverable service can verify all deliverables', function () {
    $service = app(DeliverableService::class);

    $report = $service->submit(
        $this->registration->id,
        'report',
        UploadedFile::fake()->create('r.pdf'),
    );
    $ppt = $service->submit(
        $this->registration->id,
        'presentation',
        UploadedFile::fake()->create('p.pdf'),
    );

    expect($service->areAllDeliverablesVerified($this->registration->id))->toBeFalse();

    $service->approve($report->id);
    $service->approve($ppt->id);

    expect($service->areAllDeliverablesVerified($this->registration->id))->toBeTrue();
});
