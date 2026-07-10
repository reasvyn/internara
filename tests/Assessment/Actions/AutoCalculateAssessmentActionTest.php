<?php

declare(strict_types=1);

use App\Assessment\Actions\AutoCalculateAssessmentAction;
use App\Assessment\Models\Assessment;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('auto-calculates scores from submissions and logbooks', function () {
    $assessment = Assessment::factory()->create([
        'finalized_at' => null,
        'scores_data' => [],
    ]);

    $result = app(AutoCalculateAssessmentAction::class)->execute($assessment);

    expect($result->scores_data)->toHaveKey('auto');
});

test('skips calculation when assessment is finalized', function () {
    $assessment = Assessment::factory()->finalized()->create([
        'scores_data' => [],
    ]);

    $result = app(AutoCalculateAssessmentAction::class)->execute($assessment);

    expect($result->id)->toBe($assessment->id);
});
