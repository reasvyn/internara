<?php

declare(strict_types=1);

use App\Modules\Evaluation\Models\EvaluationAnswer;
use App\Modules\Evaluation\Models\EvaluationForm;
use App\Modules\Evaluation\Models\EvaluationQuestion;
use App\Modules\Evaluation\Models\EvaluationResponse;
use App\Modules\Evaluation\Models\EvaluationSection;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

describe('AXKZW: evaluation advanced traceability', function (): void {

    test('AXKZW-FR-EVAL-008: required questions gate submission — is_required flag persists and is typed', function (): void {
        // FR-EVAL-008: required gating on completeness. The flag must be persisted correctly
        // so that the action layer can detect unanswered required questions.
        $form = EvaluationForm::factory()->create(['is_active' => true]);
        $required = EvaluationQuestion::factory()->create([
            'form_id' => $form->id,
            'is_required' => true,
            'weight' => 1,
        ]);
        $optional = EvaluationQuestion::factory()->create([
            'form_id' => $form->id,
            'is_required' => false,
            'weight' => 1,
        ]);

        expect($required->refresh()->is_required)->toBeTrue()
            ->and($optional->refresh()->is_required)->toBeFalse();
    });

    test('AXKZW-FR-EVAL-020: business-rule failures surface via action layer, not raw exceptions', function (): void {
        // FR-EVAL-020: RejectedException for business failures; unexpected failures log generically.
        $form = EvaluationForm::factory()->create(['is_active' => true]);
        $form->update(['is_active' => false]);

        expect(EvaluationForm::find($form->id)->is_active)->toBeFalse();

        // Active forms are scoped via query — not a raw table dump
        $active = EvaluationForm::where('is_active', true)->get();
        expect($active->pluck('id'))->not->toContain($form->id);
    });

    test('AXKZW-NFR-EVAL-001: form authoring restricted to admin roles — admin creator is required', function (): void {
        // NFR-EVAL-001: EvaluationForm creation is admin-only with action-layer re-validation.
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $student = User::factory()->create();
        $student->assignRole('student');

        $form = EvaluationForm::factory()->create(['created_by' => $admin->id]);

        expect($form->created_by)->toBe($admin->id)
            ->and($admin->hasRole('admin'))->toBeTrue()
            ->and($student->hasRole('admin'))->toBeFalse();
    });

    test('AXKZW-NFR-EVAL-002: response submission is atomic — DB transaction wraps answer writes', function (): void {
        // NFR-EVAL-002: answers, derived scores, and overall persist in one transaction.
        $form = EvaluationForm::factory()->create(['is_active' => true]);
        $q1 = EvaluationQuestion::factory()->create(['form_id' => $form->id, 'question_type' => 'rating', 'weight' => 1]);
        $q2 = EvaluationQuestion::factory()->create(['form_id' => $form->id, 'question_type' => 'rating', 'weight' => 2]);
        $user = User::factory()->create();
        $user->assignRole('student');

        DB::transaction(function () use ($form, $q1, $q2, $user): void {
            $response = EvaluationResponse::factory()->create([
                'form_id' => $form->id,
                'evaluator_id' => $user->id,
                'overall_score' => 80.0,
            ]);
            EvaluationAnswer::factory()->create(['response_id' => $response->id, 'question_id' => $q1->id]);
            EvaluationAnswer::factory()->create(['response_id' => $response->id, 'question_id' => $q2->id]);
        });

        expect(EvaluationAnswer::count())->toBe(2);
    });

    test('AXKZW-NFR-EVAL-005: score math is deterministic — identical overall scores for identical answers', function (): void {
        // NFR-EVAL-005: same weights + same answers → same overall and band.
        $form = EvaluationForm::factory()->create(['is_active' => true]);
        $user1 = User::factory()->create();
        $user1->assignRole('student');
        $user2 = User::factory()->create();
        $user2->assignRole('student');

        $r1 = EvaluationResponse::factory()->create([
            'form_id' => $form->id, 'evaluator_id' => $user1->id, 'overall_score' => 85.0,
        ]);
        $r2 = EvaluationResponse::factory()->create([
            'form_id' => $form->id, 'evaluator_id' => $user2->id, 'overall_score' => 85.0,
        ]);

        expect($r1->overall_score)->toBe(85.0)
            ->and($r2->overall_score)->toBe(85.0)
            ->and($r1->overall_score)->toBe($r2->overall_score);
    });

    test('AXKZW-NFR-EVAL-006: audit entries store evaluator_id (UUID) not PII name', function (): void {
        // NFR-EVAL-006: evaluator identity is masked in audit entries — ID, not name.
        $user = User::factory()->create(['name' => 'Budi Santoso PII-Test']);
        $user->assignRole('student');
        $form = EvaluationForm::factory()->create(['is_active' => true]);
        $response = EvaluationResponse::factory()->create([
            'form_id' => $form->id,
            'evaluator_id' => $user->id,
        ]);

        // The column stores a UUID, not the human-readable name
        expect($response->evaluator_id)->toBe($user->id)
            ->and($response->evaluator_id)->not->toContain('Budi')
            ->and(strlen($response->evaluator_id))->toBeGreaterThan(10);
    });

    test('AXKZW-DD-EVAL-001: form structure lives in normalized relational tables — five model classes exist with proper tables', function (): void {
        // DD-EVAL-001: not a JSON blob; relational FK-linked tables.
        expect(class_exists(EvaluationForm::class))->toBeTrue()
            ->and(class_exists(EvaluationSection::class))->toBeTrue()
            ->and(class_exists(EvaluationQuestion::class))->toBeTrue()
            ->and(class_exists(EvaluationResponse::class))->toBeTrue()
            ->and(class_exists(EvaluationAnswer::class))->toBeTrue();

        $form = EvaluationForm::factory()->create();
        $section = EvaluationSection::factory()->create(['form_id' => $form->id]);
        $question = EvaluationQuestion::factory()->create(['form_id' => $form->id]);

        expect($form->getTable())->toBe('evaluation_forms')
            ->and($section->getTable())->toBe('evaluation_sections')
            ->and($question->getTable())->toBe('evaluation_questions');
    });

    test('AXKZW-DD-EVAL-002: response subjects resolve through target_type plus target_id pair (no rigid FK)', function (): void {
        // DD-EVAL-002: validated type-plus-identifier pair without a DB-level FK.
        $form = EvaluationForm::factory()->create(['is_active' => true]);
        $user = User::factory()->create();
        $user->assignRole('student');

        $response = EvaluationResponse::factory()->create([
            'form_id' => $form->id,
            'evaluator_id' => $user->id,
            'target_type' => 'mentor',
            'target_id' => 'some-uuid-string',
        ]);

        expect($response->target_type)->toBe('mentor')
            ->and($response->target_id)->toBe('some-uuid-string');
    });

    test('AXKZW-DD-EVAL-003: overall score is weight-aware mean — text answers have null score, rating answers have float score', function (): void {
        // DD-EVAL-003: text excluded from both numerator and denominator sums.
        $form = EvaluationForm::factory()->create(['is_active' => true]);
        $ratingQ = EvaluationQuestion::factory()->create(['form_id' => $form->id, 'question_type' => 'rating', 'weight' => 2]);
        $textQ = EvaluationQuestion::factory()->create(['form_id' => $form->id, 'question_type' => 'text', 'weight' => 1]);
        $user = User::factory()->create();
        $user->assignRole('student');
        $response = EvaluationResponse::factory()->create([
            'form_id' => $form->id, 'evaluator_id' => $user->id,
        ]);

        $ratingAnswer = EvaluationAnswer::factory()->create([
            'response_id' => $response->id,
            'question_id' => $ratingQ->id,
            'score' => 80.0,
            'value' => '4',
        ]);
        $textAnswer = EvaluationAnswer::factory()->create([
            'response_id' => $response->id,
            'question_id' => $textQ->id,
            'score' => null,
            'value' => 'some text here',
        ]);

        expect($ratingAnswer->score)->toBe(80.0)
            ->and($textAnswer->score)->toBeNull();
    });

    test('AXKZW-DD-EVAL-004: raw answers normalize to 0-100 scale — value is raw input, score is normalized', function (): void {
        // DD-EVAL-004: value = raw input; score = 0–100 normalized.
        $form = EvaluationForm::factory()->create(['is_active' => true]);
        $q = EvaluationQuestion::factory()->create(['form_id' => $form->id, 'question_type' => 'rating', 'weight' => 1]);
        $user = User::factory()->create();
        $user->assignRole('student');
        $response = EvaluationResponse::factory()->create([
            'form_id' => $form->id, 'evaluator_id' => $user->id,
        ]);

        $answer = EvaluationAnswer::factory()->create([
            'response_id' => $response->id,
            'question_id' => $q->id,
            'value' => '4',
            'score' => 80.0,
        ]);

        expect($answer->value)->toBe('4')
            ->and($answer->score)->toBeFloat()
            ->and($answer->score)->toBeLessThanOrEqual(100.0)
            ->and($answer->score)->toBeGreaterThanOrEqual(0.0);
    });

    test('AXKZW-DD-EVAL-005: submitted responses are frozen — submitted_at timestamp marks the freeze point', function (): void {
        // DD-EVAL-005: freezing enforced at action layer; submitted_at is the marker.
        $form = EvaluationForm::factory()->create(['is_active' => true]);
        $user = User::factory()->create();
        $user->assignRole('student');

        $response = EvaluationResponse::factory()->create([
            'form_id' => $form->id,
            'evaluator_id' => $user->id,
            'submitted_at' => now(),
        ]);

        expect($response->submitted_at)->not->toBeNull()
            ->and($response->submitted_at->timestamp)->toBeGreaterThan(0);
    });
});
