<?php

declare(strict_types=1);

use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Evaluation\Models\EvaluationAnswer;
use App\Modules\Evaluation\Models\EvaluationForm;
use App\Modules\Evaluation\Models\EvaluationQuestion;
use App\Modules\Evaluation\Models\EvaluationResponse;
use App\Modules\Partner\Domain\Company\Models\Company;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\User\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(LazilyRefreshDatabase::class);

describe('AXKZW evaluation responses', function (): void {
    test('AXKZW-FR-EVAL-010: response links evaluator, form, and enrollment context', function (): void {
        $evaluator = User::factory()->create();
        $form = EvaluationForm::factory()->create();
        $registration = Registration::factory()->create();
        $mentor = User::factory()->create();

        $response = EvaluationResponse::factory()->create([
            'form_id' => $form->id,
            'evaluator_id' => $evaluator->id,
            'target_type' => 'mentor',
            'target_id' => $mentor->id,
            'registration_id' => $registration->id,
        ]);

        expect($response->evaluator->id)->toBe($evaluator->id)
            ->and($response->form->id)->toBe($form->id)
            ->and($response->registration->id)->toBe($registration->id);
        $this->assertDatabaseHas('evaluation_responses', [
            'id' => $response->id,
            'form_id' => $form->id,
            'evaluator_id' => $evaluator->id,
            'registration_id' => $registration->id,
        ]);
    });

    test('AXKZW-FR-EVAL-011: responses target mentor, program, and company subjects through type-plus-identifier pairs', function (): void {
        $form = EvaluationForm::factory()->create();
        $mentor = User::factory()->create();
        $program = Internship::factory()->create();
        $company = Company::factory()->create();

        $forMentor = EvaluationResponse::factory()->create([
            'form_id' => $form->id,
            'target_type' => 'mentor',
            'target_id' => $mentor->id,
        ]);
        $forProgram = EvaluationResponse::factory()->create([
            'form_id' => $form->id,
            'target_type' => 'program',
            'target_id' => $program->id,
        ]);
        $forCompany = EvaluationResponse::factory()->create([
            'form_id' => $form->id,
            'target_type' => 'company',
            'target_id' => $company->id,
        ]);

        expect($forMentor->target_type)->toBe('mentor')
            ->and($forMentor->target_id)->toBe($mentor->id)
            ->and($forProgram->target_type)->toBe('program')
            ->and($forProgram->target_id)->toBe($program->id)
            ->and($forCompany->target_type)->toBe('company')
            ->and($forCompany->target_id)->toBe($company->id);

        expect($form->responses()->where('target_type', 'company')->pluck('id'))
            ->toContain($forCompany->id);
    });

    test('AXKZW-FR-EVAL-012: enrollment linkage is optional and nulls when the registration is removed', function (): void {
        $withoutContext = EvaluationResponse::factory()->create(['registration_id' => null]);
        expect($withoutContext->fresh()->registration_id)->toBeNull()
            ->and($withoutContext->registration)->toBeNull();

        $registration = Registration::factory()->create();
        $withContext = EvaluationResponse::factory()->create(['registration_id' => $registration->id]);
        expect($withContext->registration->id)->toBe($registration->id);

        $registration->delete();

        expect($withContext->fresh()->registration_id)->toBeNull();
        $this->assertDatabaseHas('evaluation_responses', ['id' => $withContext->id]);
    });

    test('AXKZW-FR-EVAL-013: submission timestamp is stamped automatically', function (): void {
        $form = EvaluationForm::factory()->create();
        $evaluator = User::factory()->create();
        $mentor = User::factory()->create();

        $response = EvaluationResponse::create([
            'form_id' => $form->id,
            'evaluator_id' => $evaluator->id,
            'target_type' => 'mentor',
            'target_id' => $mentor->id,
        ]);

        expect($response->fresh()->submitted_at)->not->toBeNull();
        $this->assertDatabaseHas('evaluation_responses', ['id' => $response->id]);
    });

    test('AXKZW-FR-EVAL-013 and AXKZW-NFR-EVAL-003: a second answer for the same response and question is rejected', function (): void {
        $response = EvaluationResponse::factory()->create();
        $question = EvaluationQuestion::factory()->create(['form_id' => $response->form_id]);
        EvaluationAnswer::factory()->create([
            'response_id' => $response->id,
            'question_id' => $question->id,
            'value' => '4',
        ]);

        expect(fn () => EvaluationAnswer::factory()->create([
            'response_id' => $response->id,
            'question_id' => $question->id,
            'value' => '5',
        ]))->toThrow(QueryException::class);

        expect($response->answers()->where('question_id', $question->id)->count())->toBe(1);
    });

    test('AXKZW-NFR-EVAL-003: duplicate answers are impossible at the database level even bypassing Eloquent', function (): void {
        $response = EvaluationResponse::factory()->create();
        $question = EvaluationQuestion::factory()->create(['form_id' => $response->form_id]);
        EvaluationAnswer::factory()->create([
            'response_id' => $response->id,
            'question_id' => $question->id,
            'value' => '3',
        ]);

        expect(fn () => DB::table('evaluation_answers')->insert([
            'id' => (string) Str::uuid(),
            'response_id' => $response->id,
            'question_id' => $question->id,
            'value' => '3',
            'created_at' => now(),
            'updated_at' => now(),
        ]))->toThrow(QueryException::class);
    });

    test('AXKZW-NFR-EVAL-004: eager-loaded results graph keeps query count flat as answers grow', function (): void {
        $form = EvaluationForm::factory()->create();
        $section = $form->sections()->create(['title' => 'Guidance', 'order' => 1]);
        $questions = EvaluationQuestion::factory()->count(3)->create([
            'form_id' => $form->id,
            'section_id' => $section->id,
        ]);
        $responses = EvaluationResponse::factory()->count(2)->create(['form_id' => $form->id]);
        foreach ($responses as $response) {
            foreach ($questions as $question) {
                EvaluationAnswer::factory()->create([
                    'response_id' => $response->id,
                    'question_id' => $question->id,
                ]);
            }
        }

        $loadResults = fn () => EvaluationResponse::with(['answers.question', 'form'])
            ->where('form_id', $form->id)
            ->get()
            ->each(fn (EvaluationResponse $response) => $response->answers->each(
                fn (EvaluationAnswer $answer) => $answer->question->question_text.'|'.$response->form->name
            ));

        DB::flushQueryLog();
        DB::enableQueryLog();
        $loadResults();
        $baseline = count(DB::getQueryLog());

        foreach ($responses as $response) {
            $extra = EvaluationQuestion::factory()->create(['form_id' => $form->id, 'section_id' => $section->id]);
            EvaluationAnswer::factory()->create(['response_id' => $response->id, 'question_id' => $extra->id]);
        }

        DB::flushQueryLog();
        $loadResults();
        $grown = count(DB::getQueryLog());
        DB::disableQueryLog();

        expect($grown)->toBe($baseline);
    });

    test('AXKZW-FR-EVAL-010: one answer per question persists with raw value and derived score', function (): void {
        $form = EvaluationForm::factory()->create();
        $question = EvaluationQuestion::factory()->create([
            'form_id' => $form->id,
            'question_type' => 'rating_1_5',
        ]);
        $response = EvaluationResponse::factory()->create(['form_id' => $form->id, 'overall_score' => 80.0]);

        $answer = EvaluationAnswer::factory()->create([
            'response_id' => $response->id,
            'question_id' => $question->id,
            'value' => '4',
            'score' => 80.0,
        ]);

        expect($answer->fresh()->value)->toBe('4')
            ->and($answer->fresh()->score)->toBe(80.0)
            ->and($answer->response->id)->toBe($response->id)
            ->and($answer->question->id)->toBe($question->id)
            ->and($response->fresh()->overall_score)->toBe(80.0);
    });

    test('AXKZW-FR-EVAL-014: response workflow rolls back the response and answers together when scoring fails', function (): void {
        $form = EvaluationForm::factory()->create();
        $question = EvaluationQuestion::factory()->create(['form_id' => $form->id]);
        $responseId = null;

        expect(fn () => DB::transaction(function () use ($form, $question, &$responseId): void {
            $response = EvaluationResponse::create([
                'form_id' => $form->id,
                'evaluator_id' => User::factory()->create()->id,
                'target_type' => 'mentor',
                'target_id' => User::factory()->create()->id,
            ]);
            $responseId = $response->id;
            EvaluationAnswer::create([
                'response_id' => $response->id,
                'question_id' => $question->id,
                'value' => '4',
                'score' => 80.0,
            ]);
            throw new RuntimeException('scoring failed');
        }))->toThrow(RuntimeException::class, 'scoring failed');

        $this->assertDatabaseMissing('evaluation_responses', ['id' => $responseId]);
        $this->assertDatabaseMissing('evaluation_answers', ['response_id' => $responseId]);
    });

    test('AXKZW-FR-EVAL-015 and AXKZW-FR-EVAL-016: persisted answer scores and weighted overall exclude unscored text answers', function (): void {
        $form = EvaluationForm::factory()->create();
        $rating = EvaluationQuestion::factory()->create([
            'form_id' => $form->id,
            'question_type' => 'rating_1_5',
            'weight' => 3,
        ]);
        $text = EvaluationQuestion::factory()->create([
            'form_id' => $form->id,
            'question_type' => 'text',
            'weight' => 1,
        ]);
        $response = EvaluationResponse::factory()->create([
            'form_id' => $form->id,
            'overall_score' => 80.0,
        ]);

        $ratedAnswer = EvaluationAnswer::factory()->create([
            'response_id' => $response->id,
            'question_id' => $rating->id,
            'value' => '4',
            'score' => 80.0,
        ]);
        $textAnswer = EvaluationAnswer::factory()->create([
            'response_id' => $response->id,
            'question_id' => $text->id,
            'value' => 'Helpful guidance.',
            'score' => null,
        ]);

        expect($ratedAnswer->fresh()->score)->toBe(80.0)
            ->and($textAnswer->fresh()->score)->toBeNull()
            ->and($response->fresh()->overall_score)->toBe(80.0);
        $this->assertDatabaseHas('evaluation_answers', ['id' => $textAnswer->id, 'score' => null]);
    });

    test('AXKZW-FR-EVAL-017: stored overall scores remain readable for each classification band boundary', function (): void {
        foreach ([
            'excellent' => 90.0,
            'good' => 75.0,
            'satisfactory' => 60.0,
            'needs improvement' => 40.0,
            'poor' => 20.0,
        ] as $band => $score) {
            $response = EvaluationResponse::factory()->create(['overall_score' => $score]);

            expect($response->fresh()->overall_score)->toBe($score, $band.' score should persist');
            $this->assertDatabaseHas('evaluation_responses', ['id' => $response->id, 'overall_score' => $score]);
        }
    });
});
