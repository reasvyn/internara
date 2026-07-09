<?php

declare(strict_types=1);

use App\Assignment\Submission\Actions\RequestSubmissionRevisionAction;
use App\Assignment\Submission\Models\Submission;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('requests revision on submitted submission', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    $this->actingAs($user);

    $submission = Submission::factory()->create([
        'status' => 'submitted',
    ]);

    $result = app(RequestSubmissionRevisionAction::class)->execute($submission, 'Please revise the content.');

    expect($result->status->value)->toBe('revision_required');
    expect($result->feedback)->toBe('Please revise the content.');
});

test('throws exception if submission is not submitted', function () {
    $submission = Submission::factory()->create([
        'status' => 'draft',
    ]);

    app(RequestSubmissionRevisionAction::class)->execute($submission, 'feedback');
})->throws(\App\Core\Exceptions\RejectedException::class, 'Only submitted submissions can be revised.');
