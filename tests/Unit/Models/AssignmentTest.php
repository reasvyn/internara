<?php

declare(strict_types=1);

use App\Enums\Assignment\AssignmentStatus;
use App\Models\Assignment;
use App\Models\AssignmentType;
use App\Models\Internship;
use App\Models\Submission;
use Database\Factories\AssignmentFactory;
use Database\Factories\AssignmentTypeFactory;
use Database\Factories\InternshipFactory;
use Database\Factories\SubmissionFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can be created with factory', function () {
    $assignment = AssignmentFactory::new()->create();

    expect($assignment)->toBeInstanceOf(Assignment::class)
        ->and($assignment->id)->toBeUuid();
});

it('casts attributes correctly', function () {
    $assignment = AssignmentFactory::new()->create([
        'is_mandatory' => true,
        'due_date' => now()->addMonth(),
        'config' => ['allow_file_upload' => true, 'max_file_size' => 2048],
        'status' => 'published',
    ]);

    expect($assignment->is_mandatory)->toBeTrue()
        ->and($assignment->due_date)->toBeInstanceOf(Carbon\Carbon::class)
        ->and($assignment->config)->toBe(['allow_file_upload' => true, 'max_file_size' => 2048])
        ->and($assignment->status)->toBe(AssignmentStatus::PUBLISHED);
});

it('belongs to assignment type', function () {
    $type = AssignmentTypeFactory::new()->create();
    $assignment = AssignmentFactory::new()->create(['assignment_type_id' => $type->id]);

    expect($assignment->type)->toBeInstanceOf(AssignmentType::class)
        ->and($assignment->type->id)->toBe($type->id);
});

it('belongs to internship', function () {
    $internship = InternshipFactory::new()->create();
    $assignment = AssignmentFactory::new()->create(['internship_id' => $internship->id]);

    expect($assignment->internship)->toBeInstanceOf(Internship::class)
        ->and($assignment->internship->id)->toBe($internship->id);
});

it('has many submissions', function () {
    $assignment = AssignmentFactory::new()->create();
    $submissions = SubmissionFactory::new()->count(3)->create(['assignment_id' => $assignment->id]);

    expect($assignment->submissions)->toHaveCount(3)
        ->and($assignment->submissions->first())->toBeInstanceOf(Submission::class);
});

it('delegates isMandatory to entity', function () {
    $assignment = AssignmentFactory::new()->create(['is_mandatory' => true]);
    expect($assignment->asAssignmentRules()->isMandatory())->toBeTrue();

    $assignment->update(['is_mandatory' => false]);
    expect($assignment->asAssignmentRules()->isMandatory())->toBeFalse();
});

it('delegates isOverdue to entity', function () {
    $assignment = AssignmentFactory::new()->create(['due_date' => now()->subDay()]);
    expect($assignment->asAssignmentRules()->isOverdue())->toBeTrue();

    $assignment->update(['due_date' => now()->addMonth()]);
    expect($assignment->asAssignmentRules()->isOverdue())->toBeFalse();
});
