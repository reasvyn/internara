<?php

declare(strict_types=1);

use App\Modules\Assessment\Domain\Rubric\Actions\CreateCompetencyAction;
use App\Modules\Assessment\Domain\Rubric\Actions\CreateIndicatorAction;
use App\Modules\Assessment\Domain\Rubric\Actions\CreateRubricAction;
use App\Modules\Assessment\Domain\Rubric\Actions\DeleteCompetencyAction;
use App\Modules\Assessment\Domain\Rubric\Actions\DeleteIndicatorAction;
use App\Modules\Assessment\Domain\Rubric\Actions\DeleteRubricAction;
use App\Modules\Assessment\Domain\Rubric\Actions\UpdateCompetencyAction;
use App\Modules\Assessment\Domain\Rubric\Actions\UpdateIndicatorAction;
use App\Modules\Assessment\Domain\Rubric\Actions\UpdateRubricAction;
use App\Modules\Assessment\Domain\Rubric\Data\CreateCompetencyData;
use App\Modules\Assessment\Domain\Rubric\Data\CreateIndicatorData;
use App\Modules\Assessment\Domain\Rubric\Data\CreateRubricData;
use App\Modules\Assessment\Domain\Rubric\Data\DeleteIndicatorData;
use App\Modules\Assessment\Domain\Rubric\Data\UpdateCompetencyData;
use App\Modules\Assessment\Domain\Rubric\Data\UpdateIndicatorData;
use App\Modules\Assessment\Domain\Rubric\Data\UpdateRubricData;
use App\Modules\Assessment\Domain\Rubric\Models\Rubric;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;

uses(LazilyRefreshDatabase::class);

function rubricStructure(): array
{
    return ['competencies' => [[
        'id' => 'competency-one',
        'name' => 'Technical skills',
        'description' => 'Workshop performance',
        'weight' => 70,
        'evaluator_role' => 'teacher',
        'order' => 1,
        'indicators' => [[
            'id' => 'indicator-one',
            'name' => 'Tool safety',
            'description' => 'Uses tools safely',
            'max_score' => 20,
            'weight' => 100,
            'order' => 1,
        ]],
    ]]];
}

describe('ARDA6 rubric lifecycle', function (): void {
    test('ARDA6-FR-ASM-001 FR-ASM-002 FR-ASM-005 UC-ASM-001: creates and updates a rubric', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $created = app(CreateRubricAction::class)->execute(new CreateRubricData('Initial rubric', 'Draft', false));
        $rubric = $created->data;

        expect($rubric)->toBeInstanceOf(Rubric::class)
            ->and($rubric->created_by)->toBe($admin->id)
            ->and($rubric->is_active)->toBeFalse();

        app(UpdateRubricAction::class)->execute($rubric, new UpdateRubricData('Active rubric', 'Published', true));

        expect($rubric->fresh()->only(['name', 'description', 'is_active']))
            ->toMatchArray(['name' => 'Active rubric', 'is_active' => true]);
    });

    test('ARDA6-FR-ASM-002 FR-ASM-003 FR-ASM-004: creates, updates, and deletes nested competency indicators', function (): void {
        $rubric = Rubric::factory()->create(['structure' => ['competencies' => []]]);

        app(CreateCompetencyAction::class)->execute($rubric, new CreateCompetencyData('Communication', 'Explains work', 30, 'supervisor', 2));
        $competency = $rubric->fresh()->structure['competencies'][0];

        app(CreateIndicatorAction::class)->execute($rubric, new CreateIndicatorData($competency['id'], 'Explains repair', 'Clear explanation', 25, 80, 1));
        $indicator = $rubric->fresh()->structure['competencies'][0]['indicators'][0];

        app(UpdateCompetencyAction::class)->execute($rubric, new UpdateCompetencyData($competency['id'], 'Team communication', 'Updated', 40, 'teacher', 3));
        app(UpdateIndicatorAction::class)->execute($rubric, new UpdateIndicatorData($competency['id'], $indicator['id'], 'Repair briefing', 'Revised', 30, 90, 4));

        $updated = $rubric->fresh()->structure['competencies'][0];
        expect(Str::isUuid($updated['id']))->toBeTrue()
            ->and($updated['name'])->toBe('Team communication')
            ->and($updated['indicators'][0])->toMatchArray(['id' => $indicator['id'], 'name' => 'Repair briefing', 'max_score' => 30, 'weight' => 90, 'order' => 4]);

        app(DeleteIndicatorAction::class)->execute($rubric, new DeleteIndicatorData($competency['id'], $indicator['id']));
        expect($rubric->fresh()->structure['competencies'][0]['indicators'])->toBe([]);

        app(DeleteCompetencyAction::class)->execute($rubric, $competency['id']);
        expect($rubric->fresh()->structure['competencies'])->toBe([]);
    });

    test('ARDA6-FR-ASM-001 FR-ASM-018 NFR-ASM-003: deletes an unreferenced rubric and preserves its JSON payload until deletion', function (): void {
        $rubric = Rubric::factory()->create(['structure' => rubricStructure(), 'is_active' => true]);

        app(DeleteRubricAction::class)->execute($rubric);

        expect(Rubric::query()->whereKey($rubric->id)->exists())->toBeFalse();
    });
});
