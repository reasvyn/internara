<?php

declare(strict_types=1);

use App\Assessment\Models\Assessment;
use App\Assessment\Rubric\Models\Rubric;
use App\Enrollment\Registration\Models\Registration;
use App\User\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('assessment has expected fillable attributes', function () {
    $assessment = Assessment::factory()->create();

    expect($assessment->registration_id)->not->toBeNull();
    expect($assessment->rubric_id)->not->toBeNull();
    expect($assessment->evaluator_id)->not->toBeNull();
});

test('assessment belongs to registration', function () {
    $registration = Registration::factory()->create();
    $assessment = Assessment::factory()->create(['registration_id' => $registration->id]);

    expect($assessment->registration)->toBeInstanceOf(Registration::class);
    expect($assessment->registration->id)->toBe($registration->id);
});

test('assessment belongs to rubric', function () {
    $rubric = Rubric::factory()->create();
    $assessment = Assessment::factory()->create(['rubric_id' => $rubric->id]);

    expect($assessment->rubric)->toBeInstanceOf(Rubric::class);
});

test('assessment belongs to evaluator', function () {
    $user = User::factory()->create();
    $assessment = Assessment::factory()->create(['evaluator_id' => $user->id]);

    expect($assessment->evaluator)->toBeInstanceOf(User::class);
});

test('casts scores_data as array', function () {
    $assessment = Assessment::factory()->create();

    expect($assessment->scores_data)->toBeArray();
});

test('casts finalized_at as datetime', function () {
    $assessment = Assessment::factory()->finalized()->create();

    expect($assessment->finalized_at)->toBeInstanceOf(Carbon::class);
});
