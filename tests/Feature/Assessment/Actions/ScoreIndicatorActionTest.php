<?php

declare(strict_types=1);

use App\Assessment\Actions\ScoreIndicatorAction;
use App\Assessment\Models\Assessment;
use App\Assessment\Rubric\Models\Rubric;
use App\Core\Exceptions\RejectedException;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;

uses(LazilyRefreshDatabase::class);

test('scores an indicator on a non-finalized assessment', function () {
    $compId = (string) Str::uuid();
    $indId = (string) Str::uuid();

    $rubric = Rubric::factory()->create([
        'structure' => [
            'competencies' => [
                [
                    'id' => $compId,
                    'name' => 'Soft Skills',
                    'weight' => 50,
                    'evaluator_role' => 'admin',
                    'order' => 1,
                    'indicators' => [
                        [
                            'id' => $indId,
                            'name' => 'Communication',
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
        'scores_data' => [],
    ]);

    $evaluator = User::factory()->create();
    $evaluator->assignRole('admin');

    $result = app(ScoreIndicatorAction::class)->execute(
        $assessment,
        $rubric,
        $compId,
        $indId,
        85,
        $evaluator,
    );

    $compData = collect($result->scores_data['competencies'])->firstWhere('id', $compId);
    expect($compData['indicators'][$indId])->toBe(85.0);
});

test('throws when scoring a finalized assessment', function () {
    $assessment = Assessment::factory()->finalized()->create();

    $rubric = Rubric::factory()->create();
    $action = app(ScoreIndicatorAction::class);

    expect(fn () => $action->execute(
        $assessment,
        $rubric,
        'comp-id',
        'ind-id',
        85,
        User::factory()->create(),
    ))->toThrow(RejectedException::class, 'Cannot modify a finalized assessment.');
});
