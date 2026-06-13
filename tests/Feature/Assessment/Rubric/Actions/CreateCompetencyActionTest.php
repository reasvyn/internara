<?php

declare(strict_types=1);

use App\Assessment\Rubric\Actions\CreateCompetencyAction;
use App\Assessment\Rubric\Models\Rubric;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('creates competency with default evaluator role', function () {
    $rubric = Rubric::factory()->create(['structure' => ['competencies' => []]]);

    $updated = app(CreateCompetencyAction::class)->execute(
        rubric: $rubric,
        name: 'Technical Skills',
    );

    $competencies = $updated->structure['competencies'];
    expect($competencies)->toHaveCount(1);
    expect($competencies[0]['name'])->toBe('Technical Skills');
    expect($competencies[0]['evaluator_role'])->toBe('teacher');
});

test('creates competency with custom evaluator role', function () {
    $rubric = Rubric::factory()->create(['structure' => ['competencies' => []]]);

    $updated = app(CreateCompetencyAction::class)->execute(
        rubric: $rubric,
        name: 'Industry Skills',
        weight: 50,
        evaluatorRole: 'supervisor',
    );

    $competencies = $updated->structure['competencies'];
    expect($competencies[0]['evaluator_role'])->toBe('supervisor');
    expect($competencies[0]['weight'])->toBe(50);
});
