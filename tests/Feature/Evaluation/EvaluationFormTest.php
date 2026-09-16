<?php

declare(strict_types=1);

use App\Modules\Evaluation\Models\EvaluationForm;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('AXKZW evaluation forms', function (): void {
    test('AXKZW-FR-EVAL-001: creates a form with name, description, target, creator, and active flag', function (): void {
        $creator = User::factory()->create();

        $form = EvaluationForm::create([
            'name' => 'Mentor Guidance Review',
            'description' => 'End-of-period mentor feedback.',
            'target_type' => 'supervisor',
            'is_active' => true,
            'created_by' => $creator->id,
        ]);

        expect($form->id)->not->toBeNull();
        $this->assertDatabaseHas('evaluation_forms', [
            'id' => $form->id,
            'name' => 'Mentor Guidance Review',
            'description' => 'End-of-period mentor feedback.',
            'target_type' => 'supervisor',
            'created_by' => $creator->id,
        ]);
        expect($form->fresh()->is_active)->toBeTrue();
        expect($form->createdBy->id)->toBe($creator->id);
    });

    test('AXKZW-FR-EVAL-001: updates form details and toggles activation off and on', function (): void {
        $form = EvaluationForm::factory()->create(['is_active' => true, 'name' => 'Draft Form']);

        $form->update(['name' => 'Final Form', 'is_active' => false]);

        expect($form->fresh()->name)->toBe('Final Form')
            ->and($form->fresh()->is_active)->toBeFalse();

        $form->update(['is_active' => true]);

        expect($form->fresh()->is_active)->toBeTrue();
    });

    test('AXKZW-FR-EVAL-002: persists each of the five subject target types', function (): void {
        foreach (['teacher', 'supervisor', 'program', 'company', 'overall'] as $target) {
            EvaluationForm::factory()->create(['target_type' => $target]);
        }

        foreach (['teacher', 'supervisor', 'program', 'company', 'overall'] as $target) {
            $this->assertDatabaseHas('evaluation_forms', ['target_type' => $target]);
        }

        expect(EvaluationForm::whereIn('target_type', ['teacher', 'supervisor', 'program', 'company', 'overall'])->count())->toBe(5);
    });

    test('AXKZW-FR-EVAL-018: target-plus-active lookup returns only active forms', function (): void {
        $active = EvaluationForm::factory()->create(['target_type' => 'program', 'is_active' => true]);
        $inactive = EvaluationForm::factory()->inactive()->create(['target_type' => 'program']);

        $visible = EvaluationForm::where('target_type', 'program')->where('is_active', true)->get();

        expect($visible->pluck('id'))->toContain($active->id)
            ->and($visible->pluck('id'))->not->toContain($inactive->id);
    });

    test('AXKZW-UC-EVAL-001: administrator composes a form with sections and typed weighted questions then activates it', function (): void {
        $creator = User::factory()->create();

        $form = EvaluationForm::factory()->create([
            'name' => 'Mentor Review P1',
            'target_type' => 'supervisor',
            'is_active' => false,
            'created_by' => $creator->id,
        ]);

        $guidance = $form->sections()->create(['title' => 'Guidance', 'description' => 'Mentoring quality.', 'order' => 1]);
        $facilities = $form->sections()->create(['title' => 'Facilities', 'description' => 'Workshop facilities.', 'order' => 2]);

        $form->questions()->create([
            'section_id' => $guidance->id,
            'question_text' => 'How clear was the guidance?',
            'question_type' => 'rating_1_5',
            'weight' => 3,
            'order' => 1,
            'is_required' => true,
        ]);
        $form->questions()->create([
            'section_id' => $guidance->id,
            'question_text' => 'Rate guidance quality out of ten.',
            'question_type' => 'rating_1_10',
            'weight' => 2,
            'order' => 2,
            'is_required' => true,
        ]);
        $form->questions()->create([
            'section_id' => $facilities->id,
            'question_text' => 'Anything to add about the workshop?',
            'question_type' => 'text',
            'weight' => 1,
            'order' => 1,
            'is_required' => false,
        ]);

        $form->update(['is_active' => true]);
        $form->refresh();

        expect($form->is_active)->toBeTrue()
            ->and($form->sections()->count())->toBe(2)
            ->and($form->questions()->count())->toBe(3)
            ->and($form->questions()->where('is_required', true)->count())->toBe(2)
            ->and($form->questions()->sum('weight'))->toBe(6);

        $orderedSections = $form->sections()->orderBy('order')->pluck('title')->all();
        expect($orderedSections)->toBe(['Guidance', 'Facilities']);
    });
});
