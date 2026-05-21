<?php

declare(strict_types=1);

use App\Domain\Evaluation\Actions\SubmitEvaluationAction;
use App\Domain\Evaluation\Enums\EvaluationCategory;
use App\Domain\Evaluation\Models\Evaluation;
use App\Domain\User\Models\User;

beforeEach(function () {
    $this->action = app(SubmitEvaluationAction::class);
});

describe('SubmitEvaluationAction', function () {
    it('creates a mentor evaluation', function () {
        $evaluator = User::factory()->create();
        $mentor = User::factory()->create();

        $evaluation = $this->action->execute($evaluator, EvaluationCategory::MENTOR, [
            'mentor_id' => $mentor->id,
            'overall_score' => 85.0,
            'feedback' => 'Great mentor',
            'criteria_scores' => ['communication' => 90, 'responsiveness' => 80],
        ]);

        expect($evaluation)->toBeInstanceOf(Evaluation::class)
            ->and($evaluation->evaluation_type)->toBe(EvaluationCategory::MENTOR)
            ->and($evaluation->evaluator_id)->toBe($evaluator->id)
            ->and($evaluation->mentor_id)->toBe($mentor->id)
            ->and($evaluation->overall_score)->toBe(85.0);
    });

    it('creates a program evaluation', function () {
        $evaluator = User::factory()->create();

        $evaluation = $this->action->execute($evaluator, EvaluationCategory::PROGRAM, [
            'overall_score' => 78.0,
            'feedback' => 'Good program overall',
            'criteria_scores' => ['curriculum_relevance' => 80, 'administration' => 75],
        ]);

        expect($evaluation)->toBeInstanceOf(Evaluation::class)
            ->and($evaluation->evaluation_type)->toBe(EvaluationCategory::PROGRAM)
            ->and($evaluation->mentor_id)->toBeNull()
            ->and($evaluation->overall_score)->toBe(78.0);
    });

    it('creates a company evaluation with target', function () {
        $evaluator = User::factory()->create();

        $evaluation = $this->action->execute($evaluator, EvaluationCategory::COMPANY, [
            'target_type' => 'company',
            'target_id' => 'comp-123',
            'overall_score' => 90.0,
            'criteria_scores' => ['workplace_safety' => 95, 'task_relevance' => 85],
        ]);

        expect($evaluation->target_type)->toBe('company')
            ->and($evaluation->target_id)->toBe('comp-123');
    });

    it('updates an existing evaluation', function () {
        $evaluator = User::factory()->create();
        $existing = Evaluation::factory()->create([
            'evaluator_id' => $evaluator->id,
            'evaluation_type' => EvaluationCategory::PROGRAM,
            'overall_score' => 70.0,
        ]);

        $updated = $this->action->execute($evaluator, EvaluationCategory::PROGRAM, [
            'overall_score' => 95.0,
            'feedback' => 'Updated feedback',
        ], $existing);

        expect($updated->id)->toBe($existing->id)
            ->and($updated->overall_score)->toBe(95.0)
            ->and($updated->feedback)->toBe('Updated feedback');
    });

    it('creates different evaluation types', function () {
        $evaluator = User::factory()->create();

        $types = EvaluationCategory::cases();
        foreach ($types as $type) {
            $data = ['overall_score' => 80.0, 'feedback' => "Evaluating {$type->value}"];

            if ($type === EvaluationCategory::MENTOR) {
                $data['mentor_id'] = User::factory()->create()->id;
            }

            $evaluation = $this->action->execute($evaluator, $type, $data);

            expect($evaluation->evaluation_type)->toBe($type);
        }
    });
});
