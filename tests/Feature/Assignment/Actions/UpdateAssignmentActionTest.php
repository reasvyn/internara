<?php

declare(strict_types=1);

use App\Assignment\Actions\UpdateAssignmentAction;
use App\Assignment\Models\Assignment;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('updates assignment title', function () {
    $assignment = Assignment::factory()->create();

    $result = app(UpdateAssignmentAction::class)->execute(
        $assignment,
        title: 'Updated Title',
    );

    expect($result->title)->toBe('Updated Title');
});

test('updates multiple fields', function () {
    $assignment = Assignment::factory()->create();

    $result = app(UpdateAssignmentAction::class)->execute(
        $assignment,
        title: 'New Title',
        description: 'New description',
        isMandatory: true,
    );

    expect($result->title)->toBe('New Title');
    expect($result->is_mandatory)->toBeTrue();
});

test('skips null fields', function () {
    $assignment = Assignment::factory()->create(['title' => 'Original']);

    $result = app(UpdateAssignmentAction::class)->execute($assignment);

    expect($result->title)->toBe('Original');
});
