<?php

declare(strict_types=1);

use App\Assignment\Enums\AssignmentStatus;
use App\Assignment\Models\Assignment;
use App\Assignment\Submission\Models\Submission;
use App\Program\Internship\Models\Internship;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('assignment belongs to internship', function () {
    $internship = Internship::factory()->create();
    $assignment = Assignment::factory()->create(['internship_id' => $internship->id]);

    expect($assignment->internship)->toBeInstanceOf(Internship::class);
});

test('assignment has many submissions', function () {
    $assignment = Assignment::factory()->create();
    Submission::factory()->count(3)->create(['assignment_id' => $assignment->id]);

    expect($assignment->submissions)->toHaveCount(3);
});

test('assignment belongs to creator', function () {
    $user = User::factory()->create();
    $assignment = Assignment::factory()->create(['created_by' => $user->id]);

    expect($assignment->creator)->toBeInstanceOf(User::class);
});

test('casts status as enum', function () {
    $assignment = Assignment::factory()->create();

    expect($assignment->status)->toBeInstanceOf(AssignmentStatus::class);
});
