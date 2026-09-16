<?php

declare(strict_types=1);

use App\Modules\Evaluation\Models\EvaluationAnswer;
use App\Modules\Evaluation\Models\EvaluationForm;
use App\Modules\Evaluation\Models\EvaluationQuestion;
use App\Modules\Evaluation\Models\EvaluationResponse;
use App\Modules\Evaluation\Models\EvaluationSection;
use App\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;

uses(LazilyRefreshDatabase::class);

describe('AXKZW evaluation structure', function (): void {
    test('AXKZW-FR-EVAL-003: sections group questions with title, description, and order', function (): void {
        $form = EvaluationForm::factory()->create();
        $section = EvaluationSection::factory()->create([
            'form_id' => $form->id,
            'title' => 'Guidance',
            'description' => 'Mentoring quality.',
            'order' => 2,
        ]);
        $question = EvaluationQuestion::factory()->create([
            'form_id' => $form->id,
            'section_id' => $section->id,
            'question_text' => 'How clear was the guidance?',
        ]);

        expect($section->form->id)->toBe($form->id)
            ->and($question->section->id)->toBe($section->id)
            ->and($section->questions->pluck('id'))->toContain($question->id)
            ->and($form->sections->pluck('id'))->toContain($section->id);
        $this->assertDatabaseHas('evaluation_sections', [
            'id' => $section->id,
            'form_id' => $form->id,
            'title' => 'Guidance',
            'description' => 'Mentoring quality.',
            'order' => 2,
        ]);
    });

    test('AXKZW-FR-EVAL-003: deleting a section detaches its questions to form level instead of removing them', function (): void {
        $form = EvaluationForm::factory()->create();
        $section = EvaluationSection::factory()->create(['form_id' => $form->id]);
        $question = EvaluationQuestion::factory()->create([
            'form_id' => $form->id,
            'section_id' => $section->id,
        ]);
        $response = EvaluationResponse::factory()->create(['form_id' => $form->id]);
        $answer = EvaluationAnswer::factory()->create([
            'response_id' => $response->id,
            'question_id' => $question->id,
            'value' => '4',
            'score' => 80.0,
        ]);

        $section->delete();

        expect($question->fresh()->section_id)->toBeNull()
            ->and($question->fresh()->form_id)->toBe($form->id);
        $this->assertDatabaseHas('evaluation_questions', ['id' => $question->id]);
        $this->assertDatabaseHas('evaluation_answers', ['id' => $answer->id, 'value' => '4']);
    });

    test('AXKZW-FR-EVAL-004: deleting a form cascades to sections, questions, responses, and answers', function (): void {
        $form = EvaluationForm::factory()->create();
        $section = EvaluationSection::factory()->create(['form_id' => $form->id]);
        $question = EvaluationQuestion::factory()->create([
            'form_id' => $form->id,
            'section_id' => $section->id,
        ]);
        $response = EvaluationResponse::factory()->create(['form_id' => $form->id]);
        $answer = EvaluationAnswer::factory()->create([
            'response_id' => $response->id,
            'question_id' => $question->id,
        ]);

        $form->delete();

        $this->assertDatabaseMissing('evaluation_sections', ['id' => $section->id]);
        $this->assertDatabaseMissing('evaluation_questions', ['id' => $question->id]);
        $this->assertDatabaseMissing('evaluation_responses', ['id' => $response->id]);
        $this->assertDatabaseMissing('evaluation_answers', ['id' => $answer->id]);
        $this->assertDatabaseMissing('evaluation_forms', ['id' => $form->id]);
    });

    test('AXKZW-FR-EVAL-005: persists all six question types', function (): void {
        $form = EvaluationForm::factory()->create();
        $types = ['rating_1_5', 'rating_1_10', 'yes_no', 'multiple_choice', 'text', 'agreement'];

        foreach ($types as $index => $type) {
            EvaluationQuestion::factory()->create([
                'form_id' => $form->id,
                'question_type' => $type,
                'options' => $type === 'multiple_choice' ? ['A', 'B', 'C'] : null,
                'order' => $index,
            ]);
        }

        foreach ($types as $type) {
            $this->assertDatabaseHas('evaluation_questions', [
                'form_id' => $form->id,
                'question_type' => $type,
            ]);
        }

        expect($form->questions()->count())->toBe(6);
    });

    test('AXKZW-FR-EVAL-006: multiple-choice questions store their options array while ratings default to null', function (): void {
        $choice = EvaluationQuestion::factory()->multipleChoice()->create();
        $rating = EvaluationQuestion::factory()->create(['question_type' => 'rating_1_5']);

        expect($choice->fresh()->options)->toBe(['Sangat Baik', 'Baik', 'Cukup', 'Kurang'])
            ->and($rating->fresh()->options)->toBeNull();
    });

    test('AXKZW-FR-EVAL-007: weight defaults to one and persists custom values as integers', function (): void {
        $defaulted = EvaluationQuestion::factory()->create();
        $weighted = EvaluationQuestion::factory()->create(['weight' => 3]);

        expect($defaulted->fresh()->weight)->toBe(1)
            ->and($weighted->fresh()->weight)->toBe(3);
    });

    test('AXKZW-FR-EVAL-009: sections and questions read back in explicit order', function (): void {
        $form = EvaluationForm::factory()->create();
        EvaluationSection::factory()->create(['form_id' => $form->id, 'title' => 'Second', 'order' => 2]);
        EvaluationSection::factory()->create(['form_id' => $form->id, 'title' => 'First', 'order' => 1]);
        $section = $form->sections()->orderBy('order')->first();

        EvaluationQuestion::factory()->create(['form_id' => $form->id, 'section_id' => $section->id, 'question_text' => 'Q2', 'order' => 2]);
        EvaluationQuestion::factory()->create(['form_id' => $form->id, 'section_id' => $section->id, 'question_text' => 'Q1', 'order' => 1]);
        EvaluationQuestion::factory()->create(['form_id' => $form->id, 'question_text' => 'Q0 default', 'order' => 0]);

        expect($form->sections()->orderBy('order')->pluck('title')->all())->toBe(['First', 'Second'])
            ->and($section->questions()->orderBy('order')->pluck('question_text')->all())->toBe(['Q1', 'Q2'])
            ->and($form->questions()->orderBy('order')->pluck('question_text')->all())->toBe(['Q0 default', 'Q1', 'Q2']);
    });

    test('AXKZW-FR-EVAL-019: all five models use unique UUID primary keys', function (): void {
        $form = EvaluationForm::factory()->create();
        $section = EvaluationSection::factory()->create(['form_id' => $form->id]);
        $question = EvaluationQuestion::factory()->create(['form_id' => $form->id, 'section_id' => $section->id]);
        $response = EvaluationResponse::factory()->create(['form_id' => $form->id]);
        $answer = EvaluationAnswer::factory()->create(['response_id' => $response->id, 'question_id' => $question->id]);

        $ids = [$form->id, $section->id, $question->id, $response->id, $answer->id];

        foreach ($ids as $id) {
            expect(Str::isUuid($id))->toBeTrue();
        }
        expect(array_unique($ids))->toHaveCount(5);
    });

    test('AXKZW-FR-EVAL-019: typed casts round-trip on flags, weights, options, and scores', function (): void {
        $form = EvaluationForm::factory()->create(['is_active' => 1]);
        $question = EvaluationQuestion::factory()->multipleChoice()->create(['weight' => 2, 'is_required' => 1]);
        $response = EvaluationResponse::factory()->create(['overall_score' => 87.5]);
        $answer = EvaluationAnswer::factory()->create([
            'response_id' => $response->id,
            'question_id' => $question->id,
            'score' => 80.0,
        ]);

        expect($form->fresh()->is_active)->toBeTrue()
            ->and($question->fresh()->weight)->toBe(2)
            ->and($question->fresh()->is_required)->toBeTrue()
            ->and($question->fresh()->options)->toBe(['Sangat Baik', 'Baik', 'Cukup', 'Kurang'])
            ->and($response->fresh()->overall_score)->toBe(87.5)
            ->and($answer->fresh()->score)->toBe(80.0)
            ->and($response->fresh()->submitted_at)->toBeInstanceOf(Carbon::class);
    });

    test('AXKZW-FR-EVAL-019: deleting the creator nulls created_by while the form survives', function (): void {
        $creator = User::factory()->create();
        $form = EvaluationForm::factory()->create(['created_by' => $creator->id]);

        $creator->delete();

        expect($form->fresh()->created_by)->toBeNull();
        $this->assertDatabaseHas('evaluation_forms', ['id' => $form->id]);
    });

    test('AXKZW-FR-EVAL-019 and AXKZW-NFR-EVAL-007: deleting an evaluator cascades to their responses and answers', function (): void {
        $evaluator = User::factory()->create();
        $response = EvaluationResponse::factory()->create(['evaluator_id' => $evaluator->id]);
        $answer = EvaluationAnswer::factory()->create(['response_id' => $response->id]);

        $evaluator->delete();

        $this->assertDatabaseMissing('evaluation_responses', ['id' => $response->id]);
        $this->assertDatabaseMissing('evaluation_answers', ['id' => $answer->id]);
    });

    test('AXKZW-FR-EVAL-019: deleting a question or a response cascades to its answers', function (): void {
        $question = EvaluationQuestion::factory()->create();
        $response = EvaluationResponse::factory()->create();
        $first = EvaluationAnswer::factory()->create(['response_id' => $response->id, 'question_id' => $question->id]);

        $otherQuestion = EvaluationQuestion::factory()->create();
        $second = EvaluationAnswer::factory()->create(['response_id' => $response->id, 'question_id' => $otherQuestion->id]);

        $question->delete();

        $this->assertDatabaseMissing('evaluation_answers', ['id' => $first->id]);
        $this->assertDatabaseHas('evaluation_answers', ['id' => $second->id]);

        $response->delete();

        $this->assertDatabaseMissing('evaluation_answers', ['id' => $second->id]);
    });
});
