<?php

declare(strict_types=1);

use App\Assessment\Rubric\Actions\DeleteIndicatorAction;
use App\Assessment\Rubric\Models\Rubric;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;

uses(LazilyRefreshDatabase::class);

test('deletes indicator from rubric structure', function () {
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
                        ['id' => $indId, 'name' => 'To Delete', 'max_score' => 100, 'weight' => 50, 'order' => 1],
                        ['id' => (string) Str::uuid(), 'name' => 'Keep', 'max_score' => 100, 'weight' => 50, 'order' => 2],
                    ],
                ],
            ],
        ],
    ]);

    $compId = $rubric->structure['competencies'][0]['id'];
    app(DeleteIndicatorAction::class)->execute($rubric, $compId, $indId);
    $updated = $rubric->fresh();

    expect($updated->structure['competencies'][0]['indicators'])->toHaveCount(1);
    expect($updated->structure['competencies'][0]['indicators'][0]['name'])->toBe('Keep');
});
