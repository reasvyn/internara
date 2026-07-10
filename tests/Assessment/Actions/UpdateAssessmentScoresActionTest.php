<?php

declare(strict_types=1);

use App\Assessment\Actions\UpdateAssessmentScoresAction;
use App\Assessment\Models\Assessment;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('updates indicator score in assessment scores data', function () {
    $assessment = Assessment::factory()->create(['scores_data' => []]);

    $result = app(UpdateAssessmentScoresAction::class)->execute(
        $assessment,
        'competency-1',
        'indicator-1',
        90,
    );

    expect($result->scores_data['competencies']['competency-1']['indicators']['indicator-1'])->toEqual(90.0);
});

test('removes indicator score when null', function () {
    $assessment = Assessment::factory()->create([
        'scores_data' => [
            'competencies' => [
                'competency-1' => [
                    'indicators' => ['indicator-1' => 80],
                ],
            ],
        ],
    ]);

    $result = app(UpdateAssessmentScoresAction::class)->execute(
        $assessment,
        'competency-1',
        'indicator-1',
        null,
    );

    expect($result->scores_data['competencies']['competency-1']['indicators'])->not->toHaveKey('indicator-1');
});
