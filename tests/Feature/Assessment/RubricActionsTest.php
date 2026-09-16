<?php

declare(strict_types=1);

use App\Modules\Assessment\Domain\Rubric\Actions\CreateCompetencyAction;
use App\Modules\Assessment\Domain\Rubric\Actions\CreateIndicatorAction;
use App\Modules\Assessment\Domain\Rubric\Actions\CreateRubricAction;
use App\Modules\Assessment\Domain\Rubric\Actions\DeleteCompetencyAction;
use App\Modules\Assessment\Domain\Rubric\Actions\DeleteIndicatorAction;
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

uses(LazilyRefreshDatabase::class);

function rubricActionCompetency(Rubric $rubric): array
{
    return $rubric->fresh()->structure['competencies'][0];
}

describe('ARDA6 rubric action behavior', function (): void {
    test('ARDA6-FR-ASM-001 FR-ASM-005 UC-ASM-001: creates an active rubric with its creator', function (): void {
        $creator = User::factory()->create();
        $this->actingAs($creator);

        $response = app(CreateRubricAction::class)->execute(new CreateRubricData('Automotive final rubric'));
        $rubric = $response->data;

        expect($rubric)->toBeInstanceOf(Rubric::class)
            ->and($rubric->name)->toBe('Automotive final rubric')
            ->and($rubric->is_active)->toBeTrue()
            ->and($rubric->created_by)->toBe($creator->id)
            ->and($rubric->structure)->toBeNull();
    });

    test('ARDA6-FR-ASM-001 FR-ASM-005: updates rubric metadata and activation state', function (): void {
        $rubric = Rubric::factory()->create(['name' => 'Old', 'is_active' => true]);

        $result = app(UpdateRubricAction::class)->execute($rubric, new UpdateRubricData('Retired', null, false));

        expect($result->data->name)->toBe('Retired')
            ->and($result->data->description)->toBeNull()
            ->and($result->data->is_active)->toBeFalse();
    });

    test('ARDA6-FR-ASM-002 FR-ASM-003: creates a UUID-keyed competency with an empty indicator list', function (): void {
        $rubric = Rubric::factory()->create(['structure' => ['competencies' => []]]);

        $result = app(CreateCompetencyAction::class)->execute($rubric, new CreateCompetencyData('Safety', 'Workshop safety', 40, 'supervisor', 2));
        $competency = rubricActionCompetency($rubric);

        expect($result->data->id)->toBe($rubric->id)
            ->and($competency)->toMatchArray([
                'name' => 'Safety', 'description' => 'Workshop safety', 'weight' => 40,
                'evaluator_role' => 'supervisor', 'order' => 2, 'indicators' => [],
            ])
            ->and($competency['id'])->toBeString()->not->toBeEmpty();
    });

    test('ARDA6-FR-ASM-002: updates only the requested competency while preserving its indicators', function (): void {
        $rubric = Rubric::factory()->create(['structure' => ['competencies' => [
            ['id' => 'keep', 'name' => 'Keep', 'description' => null, 'weight' => 20, 'evaluator_role' => 'teacher', 'order' => 1, 'indicators' => [['id' => 'i-1', 'name' => 'Existing']]],
            ['id' => 'other', 'name' => 'Other', 'description' => null, 'weight' => 80, 'evaluator_role' => 'teacher', 'order' => 2, 'indicators' => []],
        ]]]);

        app(UpdateCompetencyAction::class)->execute($rubric, new UpdateCompetencyData('keep', 'Updated', 'New description', 60, 'supervisor', 3));
        $competencies = $rubric->fresh()->structure['competencies'];

        expect($competencies[0])->toMatchArray(['id' => 'keep', 'name' => 'Updated', 'description' => 'New description', 'weight' => 60, 'evaluator_role' => 'supervisor', 'order' => 3])
            ->and($competencies[0]['indicators'][0]['id'])->toBe('i-1')
            ->and($competencies[1]['name'])->toBe('Other');
    });

    test('ARDA6-FR-ASM-002 FR-ASM-003: deletes a competency and reindexes the JSON list', function (): void {
        $rubric = Rubric::factory()->create(['structure' => ['competencies' => [
            ['id' => 'remove', 'indicators' => []], ['id' => 'keep', 'indicators' => []],
        ]]]);

        $deleted = app(DeleteCompetencyAction::class)->execute($rubric, 'remove');

        expect($deleted->structure['competencies'])->toHaveCount(1)
            ->and($deleted->structure['competencies'][0]['id'])->toBe('keep');
    });

    test('ARDA6-FR-ASM-003: creates an indicator with maximum, weight, and display order', function (): void {
        $rubric = Rubric::factory()->create(['structure' => ['competencies' => [['id' => 'c-1', 'indicators' => []]]]]);

        app(CreateIndicatorAction::class)->execute($rubric, new CreateIndicatorData('c-1', 'Tool maintenance', 'Maintains tools', 25, 60, 4));
        $indicator = rubricActionCompetency($rubric)['indicators'][0];

        expect($indicator)->toMatchArray(['name' => 'Tool maintenance', 'description' => 'Maintains tools', 'max_score' => 25, 'weight' => 60, 'order' => 4])
            ->and($indicator['id'])->toBeString()->not->toBeEmpty();
    });

    test('ARDA6-FR-ASM-003: updates the requested indicator without changing sibling indicators', function (): void {
        $rubric = Rubric::factory()->create(['structure' => ['competencies' => [[
            'id' => 'c-1', 'indicators' => [['id' => 'i-1', 'name' => 'Old', 'description' => null, 'max_score' => 10, 'weight' => 50, 'order' => 1], ['id' => 'i-2', 'name' => 'Sibling', 'max_score' => 20]],
        ]]]]);

        app(UpdateIndicatorAction::class)->execute($rubric, new UpdateIndicatorData('c-1', 'i-1', 'New', 'Details', 25, 70, 2));
        $indicators = rubricActionCompetency($rubric)['indicators'];

        expect($indicators[0])->toMatchArray(['id' => 'i-1', 'name' => 'New', 'description' => 'Details', 'max_score' => 25, 'weight' => 70, 'order' => 2])
            ->and($indicators[1]['name'])->toBe('Sibling');
    });

    test('ARDA6-FR-ASM-003: deletes one indicator and leaves the competency intact', function (): void {
        $rubric = Rubric::factory()->create(['structure' => ['competencies' => [[
            'id' => 'c-1', 'name' => 'Skills', 'indicators' => [['id' => 'remove'], ['id' => 'keep']],
        ]]]]);

        app(DeleteIndicatorAction::class)->execute($rubric, new DeleteIndicatorData('c-1', 'remove'));

        expect(rubricActionCompetency($rubric)['name'])->toBe('Skills')
            ->and(rubricActionCompetency($rubric)['indicators'])->toBe([['id' => 'keep']]);
    });

    test('ARDA6-FR-ASM-005: exposes rubric relations and array casts', function (): void {
        $rubric = Rubric::factory()->create();

        expect($rubric->internship)->not->toBeNull()
            ->and($rubric->createdBy)->not->toBeNull()
            ->and($rubric->assessments)->toBeEmpty()
            ->and($rubric->structure)->toBeArray();
    });
});
