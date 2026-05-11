<?php

declare(strict_types=1);

use App\Actions\Assessment\ScoreIndicatorAction;
use App\Enums\Assessment\EvaluatorRole;
use Database\Factories\AssessmentFactory;
use Database\Factories\CompetencyFactory;
use Database\Factories\IndicatorFactory;
use Database\Factories\RubricFactory;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'teacher', 'guard_name' => 'web']);
    Role::create(['name' => 'supervisor', 'guard_name' => 'web']);
});

describe('execute', function () {
    it('throws if assessment is already finalized', function () {
        $assessment = AssessmentFactory::new()->finalized()->create();
        $user = UserFactory::new()->create();
        $indicator = IndicatorFactory::new()->create();

        expect(fn () => app(ScoreIndicatorAction::class)->execute($assessment, $indicator->id, 80, $user))
            ->toThrow(RuntimeException::class, 'Cannot modify a finalized assessment');
    });

    it('throws if indicator does not exist', function () {
        $assessment = AssessmentFactory::new()->create();

        expect(fn () => app(ScoreIndicatorAction::class)->execute($assessment, 'non-existent-id', 80, UserFactory::new()->create()))
            ->toThrow(ModelNotFoundException::class);
    });

    it('throws if score exceeds max_score', function () {
        $indicator = IndicatorFactory::new()->create(['max_score' => 100]);
        $assessment = AssessmentFactory::new()->create();
        $user = UserFactory::new()->create()->assignRole('super_admin');

        expect(fn () => app(ScoreIndicatorAction::class)->execute($assessment, $indicator->id, 150, $user))
            ->toThrow(RuntimeException::class, 'Score must be between 0 and 100');
    });

    it('throws if score is negative', function () {
        $indicator = IndicatorFactory::new()->create(['max_score' => 100]);
        $assessment = AssessmentFactory::new()->create();
        $user = UserFactory::new()->create()->assignRole('super_admin');

        expect(fn () => app(ScoreIndicatorAction::class)->execute($assessment, $indicator->id, -5, $user))
            ->toThrow(RuntimeException::class, 'Score must be between 0 and 100');
    });

    it('allows super_admin to score any indicator', function () {
        $rubric = RubricFactory::new()->create();
        $competency = CompetencyFactory::new()->create([
            'rubric_id' => $rubric->id,
            'evaluator_role' => EvaluatorRole::TEACHER,
        ]);
        $indicator = IndicatorFactory::new()->create([
            'competency_id' => $competency->id,
            'max_score' => 100,
        ]);
        $assessment = AssessmentFactory::new()->create(['rubric_id' => $rubric->id]);
        $admin = UserFactory::new()->create()->assignRole('super_admin');

        $result = app(ScoreIndicatorAction::class)->execute($assessment, $indicator->id, 90, $admin);

        expect($result->content['competencies'][$competency->id]['indicators'][$indicator->id])->toBe(90);
    });

    it('stores evaluator metadata in content', function () {
        $indicator = IndicatorFactory::new()->create(['max_score' => 100]);
        $assessment = AssessmentFactory::new()->create();
        $user = UserFactory::new()->create()->assignRole('super_admin');

        $result = app(ScoreIndicatorAction::class)->execute($assessment, $indicator->id, 75, $user);

        $compId = $indicator->competency_id;
        expect($result->content['competencies'][$compId]['evaluator_id'])->toBe($user->id)
            ->and($result->content['competencies'][$compId]['evaluated_at'])->not->toBeNull()
            ->and($result->content['competencies'][$compId]['indicators'][$indicator->id])->toBe(75);
    });
});
