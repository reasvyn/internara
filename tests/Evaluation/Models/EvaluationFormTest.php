<?php

declare(strict_types=1);

use App\Evaluation\Models\EvaluationForm;
use App\Evaluation\Models\EvaluationQuestion;
use App\Evaluation\Models\EvaluationResponse;
use App\Evaluation\Models\EvaluationSection;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('evaluation form factory creates valid model', function () {
    $form = EvaluationForm::factory()->create();

    expect($form)->toBeInstanceOf(EvaluationForm::class);
    expect($form->name)->not->toBeNull();
    expect($form->description)->not->toBeNull();
    expect($form->target_type)->not->toBeNull();
    expect($form->created_by)->not->toBeNull();
});

test('evaluation form casts is_active to boolean', function () {
    $form = EvaluationForm::factory()->create(['is_active' => true]);

    expect($form->is_active)->toBeTrue();

    $form = EvaluationForm::factory()->inactive()->create();

    expect($form->is_active)->toBeFalse();
});

test('evaluation form belongs to created by user', function () {
    $user = User::factory()->create();
    $form = EvaluationForm::factory()->create(['created_by' => $user->id]);

    expect($form->createdBy)->toBeInstanceOf(User::class);
    expect($form->createdBy->id)->toBe($user->id);
});

test('evaluation form has many sections', function () {
    $form = EvaluationForm::factory()->create();
    $sections = EvaluationSection::factory()->count(2)->create(['form_id' => $form->id]);

    expect($form->sections)->toHaveCount(2);
    expect($form->sections->first())->toBeInstanceOf(EvaluationSection::class);
});

test('evaluation form has many questions', function () {
    $form = EvaluationForm::factory()->create();
    $questions = EvaluationQuestion::factory()->count(2)->create(['form_id' => $form->id]);

    expect($form->questions)->toHaveCount(2);
    expect($form->questions->first())->toBeInstanceOf(EvaluationQuestion::class);
});

test('evaluation form has many responses', function () {
    $form = EvaluationForm::factory()->create();
    $responses = EvaluationResponse::factory()->count(2)->create(['form_id' => $form->id]);

    expect($form->responses)->toHaveCount(2);
    expect($form->responses->first())->toBeInstanceOf(EvaluationResponse::class);
});

test('evaluation form fillable attributes are mass assignable', function () {
    $form = EvaluationForm::factory()->create([
        'name' => 'Test Form',
        'description' => 'Test description',
        'target_type' => 'mentor',
        'is_active' => true,
    ]);

    expect($form->name)->toBe('Test Form');
    expect($form->description)->toBe('Test description');
    expect($form->target_type)->toBe('mentor');
    expect($form->is_active)->toBeTrue();
});
