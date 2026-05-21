<?php

declare(strict_types=1);

use App\Domain\Evaluation\Actions\DeleteEvaluationAction;
use App\Domain\Evaluation\Actions\EvaluateMentorAction;
use App\Domain\Evaluation\Actions\SubmitEvaluationAction;
use App\Domain\Evaluation\Enums\EvaluationCategory;
use App\Domain\Evaluation\Models\Evaluation;
use App\Domain\User\Models\User;

describe('SubmitEvaluationAction', function () {
    it('creates a mentor evaluation', function () {
        $evaluator = User::factory()->create();
        $mentor = User::factory()->create();

        $evaluation = app(SubmitEvaluationAction::class)->execute($evaluator, EvaluationCategory::MENTOR, [
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

    it('creates an evaluation with target', function () {
        $evaluator = User::factory()->create();

        $evaluation = app(SubmitEvaluationAction::class)->execute($evaluator, EvaluationCategory::COMPANY, [
            'target_type' => 'company',
            'target_id' => 'comp-123',
            'overall_score' => 90.0,
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

        $updated = app(SubmitEvaluationAction::class)->execute($evaluator, EvaluationCategory::PROGRAM, [
            'overall_score' => 95.0,
            'feedback' => 'Updated feedback',
        ], $existing);

        expect($updated->id)->toBe($existing->id)
            ->and($updated->overall_score)->toBe(95.0)
            ->and($updated->feedback)->toBe('Updated feedback');
    });
});

describe('EvaluateMentorAction', function () {
    it('creates a mentor evaluation for a specific mentor', function () {
        $evaluator = User::factory()->create();
        $mentor = User::factory()->create();

        $evaluation = app(EvaluateMentorAction::class)->execute($evaluator, $mentor, [
            'overall_score' => 92.0,
            'feedback' => 'Excellent guidance',
        ]);

        expect($evaluation)->toBeInstanceOf(Evaluation::class)
            ->and($evaluation->evaluator_id)->toBe($evaluator->id)
            ->and($evaluation->mentor_id)->toBe($mentor->id)
            ->and($evaluation->evaluation_type)->toBe(EvaluationCategory::MENTOR)
            ->and($evaluation->overall_score)->toBe(92.0);
    });

    it('updates an existing mentor evaluation', function () {
        $evaluator = User::factory()->create();
        $mentor = User::factory()->create();
        $existing = Evaluation::factory()->mentor()->create([
            'evaluator_id' => $evaluator->id,
            'mentor_id' => $mentor->id,
            'overall_score' => 80.0,
        ]);

        $updated = app(EvaluateMentorAction::class)->execute($evaluator, $mentor, [
            'overall_score' => 98.0,
            'feedback' => 'Even better updated feedback',
        ], $existing);

        expect($updated->id)->toBe($existing->id)
            ->and($updated->overall_score)->toBe(98.0)
            ->and($updated->feedback)->toBe('Even better updated feedback');
    });
});

describe('DeleteEvaluationAction', function () {
    it('deletes an evaluation', function () {
        $evaluation = Evaluation::factory()->create();

        app(DeleteEvaluationAction::class)->execute($evaluation);

        expect(Evaluation::find($evaluation->id))->toBeNull();
    });
});
