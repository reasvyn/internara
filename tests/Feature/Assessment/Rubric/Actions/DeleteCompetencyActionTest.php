<?php

declare(strict_types=1);

use App\Assessment\Rubric\Actions\DeleteCompetencyAction;
use App\Assessment\Rubric\Models\Rubric;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;

uses(LazilyRefreshDatabase::class);

test('deletes competency from rubric structure', function () {
    $compId = (string) Str::uuid();
    $rubric = Rubric::factory()->create([
        'structure' => [
            'competencies' => [
                ['id' => $compId, 'name' => 'To Delete', 'weight' => 100, 'evaluator_role' => 'teacher', 'order' => 1, 'indicators' => []],
                ['id' => (string) Str::uuid(), 'name' => 'Keep', 'weight' => 100, 'evaluator_role' => 'teacher', 'order' => 2, 'indicators' => []],
            ],
        ],
    ]);

    $updated = app(DeleteCompetencyAction::class)->execute($rubric, $compId);

    expect($updated->structure['competencies'])->toHaveCount(1);
    expect($updated->structure['competencies'][0]['name'])->toBe('Keep');
});
