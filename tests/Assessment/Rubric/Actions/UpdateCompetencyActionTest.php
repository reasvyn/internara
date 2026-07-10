<?php

declare(strict_types=1);

use App\Assessment\Rubric\Actions\UpdateCompetencyAction;
use App\Assessment\Rubric\Models\Rubric;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;

uses(LazilyRefreshDatabase::class);

test('updates competency fields in rubric structure', function () {
    $compId = (string) Str::uuid();
    $rubric = Rubric::factory()->create([
        'structure' => [
            'competencies' => [
                ['id' => $compId, 'name' => 'Old Name', 'description' => 'Old', 'weight' => 50, 'evaluator_role' => 'teacher', 'order' => 1, 'indicators' => []],
            ],
        ],
    ]);

    $updated = app(UpdateCompetencyAction::class)->execute(
        rubric: $rubric,
        competencyId: $compId,
        name: 'Updated Name',
        description: 'Updated description',
        weight: 75,
        evaluatorRole: 'supervisor',
    );

    $comp = $updated->structure['competencies'][0];
    expect($comp['name'])->toBe('Updated Name');
    expect($comp['weight'])->toBe(75);
    expect($comp['evaluator_role'])->toBe('supervisor');
});
