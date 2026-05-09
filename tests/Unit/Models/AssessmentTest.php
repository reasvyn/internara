<?php

declare(strict_types=1);

use App\Models\Assessment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can be created with factory', function () {
    $assessment = Assessment::factory()->create();

    expect($assessment)->toBeInstanceOf(Assessment::class)
        ->and($assessment->id)->toBeUuid();
});

it('delegates isFinalized to entity', function () {
    $assessment = Assessment::factory()->create(['finalized_at' => now()]);
    expect($assessment->asAssessmentResult()->isFinalized())->toBeTrue();

    $assessment->update(['finalized_at' => null]);
    expect($assessment->asAssessmentResult()->isFinalized())->toBeFalse();
});

it('delegates calculateTotalScore to entity', function () {
    $assessment = Assessment::factory()->create([
        'content' => [
            ['score' => 10],
            ['score' => 20],
        ],
    ]);

    expect($assessment->asAssessmentResult()->calculateTotalScore())->toBe(30.0);
});
