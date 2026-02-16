<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Modules\Assignment\Database\Seeders\AssignmentSeeder;
use Modules\Assignment\Models\Assignment;
use Modules\Assignment\Services\Contracts\AssignmentService;
use Modules\Assignment\Services\Contracts\SubmissionService;
use Modules\Internship\Models\Internship;
use Modules\Internship\Models\InternshipRegistration;
use Modules\User\Models\User;

beforeEach(function () {
    $this->seed(AssignmentSeeder::class);
    $this->assignmentService = app(AssignmentService::class);
    $this->submissionService = app(SubmissionService::class);
});

test('it can create default assignments for an internship program', function () {
    $internship = Internship::factory()->create();

    $this->assignmentService->createDefaults($internship->id);

    $this->assertDatabaseHas('assignments', [
        'internship_id' => $internship->id,
        'title' => 'Laporan Kegiatan PKL',
    ]);

    $this->assertDatabaseHas('assignments', [
        'internship_id' => $internship->id,
        'title' => 'Presentasi Kegiatan PKL',
    ]);

    expect(Assignment::where('internship_id', $internship->id)->count())->toBe(2);
});

test('it determines fulfillment complete when all mandatory assignments are verified', function () {
    $internship = Internship::factory()->create();
    $student = User::factory()->create();
    $registration = InternshipRegistration::factory()->create([
        'internship_id' => $internship->id,
        'student_id' => $student->id,
    ]);

    $this->assignmentService->createDefaults($internship->id);
    $assignments = Assignment::where('internship_id', $internship->id)->get();

    expect($this->assignmentService->isFulfillmentComplete($registration->id))->toBeFalse();

    $this->actingAs($student);

    foreach ($assignments as $assignment) {
        $submission = $this->submissionService->submit(
            $registration->id,
            $assignment->id,
            UploadedFile::fake()->create('doc.pdf'),
        );

        expect($this->assignmentService->isFulfillmentComplete($registration->id))->toBeFalse();

        $this->submissionService->verify($submission->id);
    }

    expect($this->assignmentService->isFulfillmentComplete($registration->id))->toBeTrue();
});

test('it allows completing if no mandatory assignments exist', function () {
    $internship = Internship::factory()->create();
    $registration = InternshipRegistration::factory()->create(['internship_id' => $internship->id]);

    // No assignments created for this internship
    expect($this->assignmentService->isFulfillmentComplete($registration->id))->toBeTrue();
});
