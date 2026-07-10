<?php

declare(strict_types=1);

use App\Evaluation\Models\EvaluationForm;
use App\Evaluation\Models\EvaluationQuestion;
use App\Evaluation\Models\EvaluationSection;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('evaluation section factory creates valid model', function () {
    $section = EvaluationSection::factory()->create();

    expect($section)->toBeInstanceOf(EvaluationSection::class);
    expect($section->form_id)->not->toBeNull();
    expect($section->title)->not->toBeNull();
    expect($section->description)->not->toBeNull();
    expect($section->order)->not->toBeNull();
});

test('evaluation section belongs to form', function () {
    $form = EvaluationForm::factory()->create();
    $section = EvaluationSection::factory()->create(['form_id' => $form->id]);

    expect($section->form)->toBeInstanceOf(EvaluationForm::class);
    expect($section->form->id)->toBe($form->id);
});

test('evaluation section has many questions', function () {
    $section = EvaluationSection::factory()->create();
    $questions = EvaluationQuestion::factory()->count(2)->create(['section_id' => $section->id]);

    expect($section->questions)->toHaveCount(2);
    expect($section->questions->first())->toBeInstanceOf(EvaluationQuestion::class);
});

test('evaluation section fillable attributes are mass assignable', function () {
    $form = EvaluationForm::factory()->create();
    $section = EvaluationSection::factory()->create([
        'form_id' => $form->id,
        'title' => 'Section Title',
        'description' => 'Section description',
        'order' => 3,
    ]);

    expect($section->title)->toBe('Section Title');
    expect($section->description)->toBe('Section description');
    expect($section->order)->toBe(3);
});
