<?php

declare(strict_types=1);

use App\Assessment\Rubric\Actions\CreateIndicatorAction;
use App\Assessment\Rubric\Models\Rubric;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;

uses(LazilyRefreshDatabase::class);

test('creates indicator with default max score', function () {
    $compId = (string) Str::uuid();
    $rubric = Rubric::factory()->create([
        'structure' => [
            'competencies' => [
                ['id' => $compId, 'name' => 'Test', 'weight' => 100, 'evaluator_role' => 'teacher', 'order' => 1, 'indicators' => []],
            ],
        ],
    ]);

    $updated = app(CreateIndicatorAction::class)->execute(
        rubric: $rubric,
        competencyId: $compId,
        name: 'Communication',
    );

    $indicators = $updated->structure['competencies'][0]['indicators'];
    expect($indicators)->toHaveCount(1);
    expect($indicators[0]['max_score'])->toBe(100);
});

test('creates indicator with custom max score', function () {
    $compId = (string) Str::uuid();
    $rubric = Rubric::factory()->create([
        'structure' => [
            'competencies' => [
                ['id' => $compId, 'name' => 'Test', 'weight' => 100, 'evaluator_role' => 'teacher', 'order' => 1, 'indicators' => []],
            ],
        ],
    ]);

    $updated = app(CreateIndicatorAction::class)->execute(
        rubric: $rubric,
        competencyId: $compId,
        name: 'Advanced Task',
        maxScore: 200,
        weight: 50,
    );

    $indicators = $updated->structure['competencies'][0]['indicators'];
    expect($indicators[0]['max_score'])->toBe(200);
    expect($indicators[0]['weight'])->toBe(50);
});
