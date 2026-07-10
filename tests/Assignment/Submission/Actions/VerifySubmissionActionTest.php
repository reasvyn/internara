<?php

declare(strict_types=1);

use App\Assignment\Submission\Actions\VerifySubmissionAction;
use App\Assignment\Submission\Models\Submission;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('verifies submission', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    $this->actingAs($user);

    $submission = Submission::factory()->create();

    $result = app(VerifySubmissionAction::class)->execute($submission);

    expect($result->status->value)->toBe('verified');
    expect($result->verified_by)->toBe($user->id);
});
