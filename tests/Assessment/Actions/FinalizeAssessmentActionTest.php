<?php

declare(strict_types=1);

use App\Assessment\Actions\FinalizeAssessmentAction;
use App\Assessment\Models\Assessment;
use App\Assessment\Rubric\Models\Rubric;
use App\Core\Exceptions\RejectedException;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('finalizes assessment with scored competencies', function () {
    $rubric = Rubric::factory()->create([
        'structure' => [
            'competencies' => [
                [
                    'id' => 'comp-1',
                    'name' => 'Technical Skills',
                    'weight' => 100,
                    'evaluator_role' => 'teacher',
                    'order' => 1,
                    'indicators' => [
                        [
                            'id' => 'ind-1',
                            'name' => 'Coding',
                            'max_score' => 100,
                            'weight' => 100,
                            'order' => 1,
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $assessment = Assessment::factory()->create([
        'rubric_id' => $rubric->id,
        'finalized_at' => null,
        'scores_data' => [
            'competencies' => [
                'comp-1' => [
                    'indicators' => ['ind-1' => 85],
                ],
            ],
        ],
    ]);

    $finalizer = User::factory()->create();

    $result = app(FinalizeAssessmentAction::class)->execute($assessment, $finalizer);

    expect($result->finalized_at)->not->toBeNull();
    expect($result->score)->toBeFloat();
});

test('throws when assessment is already finalized', function () {
    $assessment = Assessment::factory()->finalized()->create();

    app(FinalizeAssessmentAction::class)->execute($assessment, User::factory()->create());
})->throws(RejectedException::class, 'Assessment is already finalized.');

test('throws when no rubric is assigned', function () {
    $assessment = Assessment::factory()->create(['rubric_id' => null, 'finalized_at' => null]);

    app(FinalizeAssessmentAction::class)->execute($assessment, User::factory()->create());
})->throws(RejectedException::class, 'Assessment must have a rubric to finalize.');

test('throws when no competencies have been scored', function () {
    $rubric = Rubric::factory()->create([
        'structure' => [
            'competencies' => [
                [
                    'id' => 'comp-1',
                    'name' => 'Empty',
                    'weight' => 100,
                    'evaluator_role' => 'supervisor',
                    'order' => 1,
                    'indicators' => [
                        [
                            'id' => 'ind-1',
                            'name' => 'Empty',
                            'max_score' => 100,
                            'weight' => 100,
                            'order' => 1,
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $assessment = Assessment::factory()->create([
        'rubric_id' => $rubric->id,
        'finalized_at' => null,
        'scores_data' => ['competencies' => []],
    ]);

    app(FinalizeAssessmentAction::class)->execute($assessment, User::factory()->create());
})->throws(RejectedException::class, 'No competencies have been scored.');
