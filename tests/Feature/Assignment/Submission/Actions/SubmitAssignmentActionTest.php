<?php

declare(strict_types=1);

use App\Assignment\Models\Assignment;
use App\Assignment\Submission\Actions\SubmitAssignmentAction;
use App\Assignment\Submission\Models\Submission;
use App\Core\Exceptions\RejectedException;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('submits assignment for published assignment', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    $assignment = Assignment::factory()->create(['status' => 'published']);

    $submission = app(SubmitAssignmentAction::class)->execute(
        $student,
        $assignment,
        ['content' => 'My submission content'],
    );

    expect($submission)->toBeInstanceOf(Submission::class);
});

test('throws when submitting to unpublished assignment', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    $assignment = Assignment::factory()->create(['status' => 'draft']);

    app(SubmitAssignmentAction::class)->execute(
        $student,
        $assignment,
        ['content' => 'Content'],
    );
})->throws(RejectedException::class, 'Cannot submit to unpublished assignment.');
