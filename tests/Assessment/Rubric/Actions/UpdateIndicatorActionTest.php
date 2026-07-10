<?php

declare(strict_types=1);

use App\Assessment\Rubric\Actions\UpdateIndicatorAction;
use App\Assessment\Rubric\Models\Rubric;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;

uses(LazilyRefreshDatabase::class);

test('updates indicator fields in rubric structure', function () {
    $indId = (string) Str::uuid();
    $rubric = Rubric::factory()->create([
        'structure' => [
            'competencies' => [
                [
                    'id' => (string) Str::uuid(),
                    'name' => 'Test',
                    'weight' => 100,
                    'evaluator_role' => 'teacher',
                    'order' => 1,
                    'indicators' => [
                        ['id' => $indId, 'name' => 'Old', 'description' => 'Old', 'max_score' => 100, 'weight' => 50, 'order' => 1],
                    ],
                ],
            ],
        ],
    ]);

    $compId = $rubric->structure['competencies'][0]['id'];
    $updated = app(UpdateIndicatorAction::class)->execute(
        rubric: $rubric,
        competencyId: $compId,
        indicatorId: $indId,
        name: 'Updated Indicator',
        description: 'Updated',
        maxScore: 150,
        weight: 75,
    );

    $ind = $updated->structure['competencies'][0]['indicators'][0];
    expect($ind['name'])->toBe('Updated Indicator');
    expect($ind['max_score'])->toBe(150);
    expect($ind['weight'])->toBe(75);
});
