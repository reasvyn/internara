<?php

declare(strict_types=1);

use App\Modules\Assessment\Domain\Rubric\Models\Rubric;
use App\Modules\Assessment\Enums\EvaluatorRole;
use App\Modules\Assessment\Models\Assessment;
use App\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('ARDA6 assessment contracts', function (): void {
    test('ARDA6-FR-ASM-011: casts scores, score, and finalization timestamp', function (): void {
        $assessment = Assessment::factory()->create([
            'scores_data' => ['competencies' => []],
            'score' => '84.5',
            'finalized_at' => '2026-06-01 10:00:00',
        ]);

        expect($assessment->scores_data)->toBeArray()
            ->and($assessment->score)->toBe(84.5)
            ->and($assessment->finalized_at)->toBeInstanceOf(Carbon::class);
    });

    test('ARDA6-FR-ASM-011: exposes registration, rubric, and evaluator relations', function (): void {
        $evaluator = User::factory()->create();
        $rubric = Rubric::factory()->create();
        $assessment = Assessment::factory()->create(['evaluator_id' => $evaluator->id, 'rubric_id' => $rubric->id]);

        expect($assessment->registration)->not->toBeNull()
            ->and($assessment->rubric->id)->toBe($rubric->id)
            ->and($assessment->evaluator->id)->toBe($evaluator->id);
    });

    test('ARDA6-FR-ASM-011: bridges a persisted assessment into an AssessmentResult snapshot', function (): void {
        $assessment = Assessment::factory()->create(['score' => 72.25, 'scores_data' => ['competencies' => []]]);

        $result = $assessment->asAssessmentResult();

        expect($result->isFinalized())->toBeFalse()->and($result->calculateTotalScore())->toBe(0.0);
    });

    test('ARDA6-FR-ASM-012: defines exactly the supported evaluator roles', function (): void {
        expect(EvaluatorRole::cases())->toHaveCount(4)
            ->and(EvaluatorRole::ADMIN->value)->toBe('admin')
            ->and(EvaluatorRole::TEACHER->value)->toBe('teacher')
            ->and(EvaluatorRole::SUPERVISOR->value)->toBe('supervisor')
            ->and(EvaluatorRole::SYSTEM->value)->toBe('system');
    });

    test('ARDA6-FR-ASM-008: evaluator role labels resolve through translations', function (): void {
        foreach (EvaluatorRole::cases() as $role) {
            expect($role->label())->toBeString()->not->toBe($role->value);
        }
    });

    test('ARDA6-FR-ASM-005: rubric structure remains an array after persistence', function (): void {
        $structure = ['competencies' => [['id' => 'c-1', 'indicators' => []]]];
        $rubric = Rubric::factory()->create(['structure' => $structure]);

        expect($rubric->fresh()->structure)->toEqual($structure);
    });

    test('ARDA6-FR-ASM-017: finalized timestamps distinguish drafts from frozen records', function (): void {
        $draft = Assessment::factory()->create(['finalized_at' => null]);
        $finalized = Assessment::factory()->finalized()->create();

        expect($draft->asAssessmentResult()->isFinalized())->toBeFalse()
            ->and($finalized->asAssessmentResult()->isFinalized())->toBeTrue();
    });
});
