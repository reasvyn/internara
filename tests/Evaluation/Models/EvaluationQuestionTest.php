<?php

declare(strict_types=1);

use App\Evaluation\Models\EvaluationAnswer;
use App\Evaluation\Models\EvaluationForm;
use App\Evaluation\Models\EvaluationQuestion;
use App\Evaluation\Models\EvaluationSection;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('evaluation question factory creates valid model', function () {
    $question = EvaluationQuestion::factory()->create();

    expect($question)->toBeInstanceOf(EvaluationQuestion::class);
    expect($question->form_id)->not->toBeNull();
    expect($question->question_text)->not->toBeNull();
    expect($question->question_type)->not->toBeNull();
});

test('evaluation question belongs to form', function () {
    $form = EvaluationForm::factory()->create();
    $question = EvaluationQuestion::factory()->create(['form_id' => $form->id]);

    expect($question->form)->toBeInstanceOf(EvaluationForm::class);
    expect($question->form->id)->toBe($form->id);
});

test('evaluation question belongs to section', function () {
    $section = EvaluationSection::factory()->create();
    $question = EvaluationQuestion::factory()->create(['section_id' => $section->id]);

    expect($question->section)->toBeInstanceOf(EvaluationSection::class);
    expect($question->section->id)->toBe($section->id);
});

test('evaluation question has many answers', function () {
    $question = EvaluationQuestion::factory()->create();
    $answers = EvaluationAnswer::factory()->count(2)->create(['question_id' => $question->id]);

    expect($question->answers)->toHaveCount(2);
    expect($question->answers->first())->toBeInstanceOf(EvaluationAnswer::class);
});

test('evaluation question casts options to json', function () {
    $options = ['Option 1', 'Option 2', 'Option 3'];
    $question = EvaluationQuestion::factory()->multipleChoice()->create(['options' => $options]);

    expect($question->options)->toBe($options);
});

test('evaluation question casts weight and order to integer', function () {
    $question = EvaluationQuestion::factory()->create([
        'weight' => 5,
        'order' => 2,
    ]);

    expect($question->weight)->toBe(5);
    expect($question->order)->toBe(2);
});

test('evaluation question casts is_required to boolean', function () {
    $question = EvaluationQuestion::factory()->create(['is_required' => true]);

    expect($question->is_required)->toBeTrue();
});

test('evaluation question fillable attributes are mass assignable', function () {
    $form = EvaluationForm::factory()->create();
    $question = EvaluationQuestion::factory()->create([
        'question_text' => 'How are you?',
        'question_type' => 'rating_1_5',
        'weight' => 3,
        'order' => 1,
        'is_required' => true,
    ]);

    expect($question->question_text)->toBe('How are you?');
    expect($question->question_type)->toBe('rating_1_5');
    expect($question->weight)->toBe(3);
    expect($question->order)->toBe(1);
    expect($question->is_required)->toBeTrue();
});
