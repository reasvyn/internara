<?php

declare(strict_types=1);

use App\Enums\Assignment\SubmissionStatus;
use App\Models\Assignment;
use App\Models\Registration;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can be created with factory', function () {
    $submission = Submission::factory()->create();

    expect($submission)->toBeInstanceOf(Submission::class)
        ->and($submission->id)->toBeUuid();
});

it('casts attributes correctly', function () {
    $submission = Submission::factory()->create([
        'metadata' => ['file_type' => 'pdf', 'pages' => 5],
        'submitted_at' => now(),
        'graded_at' => now(),
        'status' => SubmissionStatus::VERIFIED,
    ]);

    expect($submission->metadata)->toBe(['file_type' => 'pdf', 'pages' => 5])
        ->and($submission->submitted_at)->toBeInstanceOf(Carbon\Carbon::class)
        ->and($submission->graded_at)->toBeInstanceOf(Carbon\Carbon::class)
        ->and($submission->status)->toBe(SubmissionStatus::VERIFIED);
});

it('belongs to assignment', function () {
    $assignment = Assignment::factory()->create();
    $submission = Submission::factory()->create(['assignment_id' => $assignment->id]);

    expect($submission->assignment)->toBeInstanceOf(Assignment::class)
        ->and($submission->assignment->id)->toBe($assignment->id);
});

it('belongs to registration', function () {
    $registration = Registration::factory()->create();
    $submission = Submission::factory()->create(['registration_id' => $registration->id]);

    expect($submission->registration)->toBeInstanceOf(Registration::class)
        ->and($submission->registration->id)->toBe($registration->id);
});

it('belongs to student', function () {
    $student = User::factory()->create();
    $submission = Submission::factory()->create(['student_id' => $student->id]);

    expect($submission->student)->toBeInstanceOf(User::class)
        ->and($submission->student->id)->toBe($student->id);
});

it('belongs to grader', function () {
    $grader = User::factory()->create();
    $submission = Submission::factory()->create(['graded_by' => $grader->id]);

    expect($submission->grader)->toBeInstanceOf(User::class)
        ->and($submission->grader->id)->toBe($grader->id);
});

it('delegates status checks to entity', function () {
    $submission = Submission::factory()->create(['status' => SubmissionStatus::VERIFIED]);
    expect($submission->asSubmissionState()->isVerified())->toBeTrue();

    $submission->update(['status' => SubmissionStatus::DRAFT]);
    expect($submission->asSubmissionState()->canBeEdited())->toBeTrue();
});
