<?php

declare(strict_types=1);

use App\Assignment\Submission\Actions\GradeSubmissionAction;
use App\Assignment\Submission\Models\Submission;
use App\Core\Exceptions\RejectedException;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('grades submission with valid score', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    $this->actingAs($user);

    $submission = Submission::factory()->create();

    $result = app(GradeSubmissionAction::class)->execute($submission, 85, 'Good work');

    expect($result->score)->toBe(85);
    expect($result->status->value)->toBe('graded');
});

test('throws when score is out of range', function () {
    $submission = Submission::factory()->create();

    app(GradeSubmissionAction::class)->execute($submission, 150);
})->throws(RejectedException::class, 'Score must be between 0 and 100.');
