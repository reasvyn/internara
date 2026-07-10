<?php

declare(strict_types=1);

use App\Assignment\Models\Assignment;
use App\Assignment\Submission\Models\Submission;
use App\Enrollment\Registration\Models\Registration;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('submission belongs to assignment', function () {
    $assignment = Assignment::factory()->create();
    $submission = Submission::factory()->create(['assignment_id' => $assignment->id]);

    expect($submission->assignment)->toBeInstanceOf(Assignment::class);
});

test('submission belongs to registration', function () {
    $registration = Registration::factory()->create();
    $submission = Submission::factory()->create(['registration_id' => $registration->id]);

    expect($submission->registration)->toBeInstanceOf(Registration::class);
});

test('submission belongs to student', function () {
    $student = User::factory()->create();
    $submission = Submission::factory()->create(['student_id' => $student->id]);

    expect($submission->student)->toBeInstanceOf(User::class);
});

test('submission belongs to grader', function () {
    $grader = User::factory()->create();
    $submission = Submission::factory()->create(['graded_by' => $grader->id]);

    expect($submission->grader)->toBeInstanceOf(User::class);
});
