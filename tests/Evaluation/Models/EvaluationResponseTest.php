<?php

declare(strict_types=1);

use App\Enrollment\Registration\Models\Registration;
use App\Evaluation\Models\EvaluationAnswer;
use App\Evaluation\Models\EvaluationForm;
use App\Evaluation\Models\EvaluationResponse;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;

uses(LazilyRefreshDatabase::class);

test('evaluation response factory creates valid model', function () {
    $response = EvaluationResponse::factory()->create();

    expect($response)->toBeInstanceOf(EvaluationResponse::class);
    expect($response->form_id)->not->toBeNull();
    expect($response->evaluator_id)->not->toBeNull();
    expect($response->target_type)->not->toBeNull();
    expect($response->target_id)->not->toBeNull();
});

test('evaluation response belongs to form', function () {
    $form = EvaluationForm::factory()->create();
    $response = EvaluationResponse::factory()->create(['form_id' => $form->id]);

    expect($response->form)->toBeInstanceOf(EvaluationForm::class);
    expect($response->form->id)->toBe($form->id);
});

test('evaluation response belongs to evaluator', function () {
    $evaluator = User::factory()->create();
    $response = EvaluationResponse::factory()->create(['evaluator_id' => $evaluator->id]);

    expect($response->evaluator)->toBeInstanceOf(User::class);
    expect($response->evaluator->id)->toBe($evaluator->id);
});

test('evaluation response belongs to registration', function () {
    $registration = Registration::factory()->create();
    $response = EvaluationResponse::factory()->create(['registration_id' => $registration->id]);

    expect($response->registration)->toBeInstanceOf(Registration::class);
    expect($response->registration->id)->toBe($registration->id);
});

test('evaluation response has many answers', function () {
    $response = EvaluationResponse::factory()->create();
    $answers = EvaluationAnswer::factory()->count(2)->create(['response_id' => $response->id]);

    expect($response->answers)->toHaveCount(2);
    expect($response->answers->first())->toBeInstanceOf(EvaluationAnswer::class);
});

test('evaluation response casts overall_score to float', function () {
    $response = EvaluationResponse::factory()->create(['overall_score' => 92.5]);

    expect($response->overall_score)->toBe(92.5);
    expect($response->overall_score)->toBeFloat();
});

test('evaluation response casts submitted_at to datetime', function () {
    $response = EvaluationResponse::factory()->create(['submitted_at' => now()]);

    expect($response->submitted_at)->toBeInstanceOf(Carbon::class);
});

test('evaluation response fillable attributes are mass assignable', function () {
    $response = EvaluationResponse::factory()->create([
        'target_type' => 'mentor',
        'overall_score' => 85.0,
        'notes' => 'Good performance.',
    ]);

    expect($response->target_type)->toBe('mentor');
    expect($response->overall_score)->toBe(85.0);
    expect($response->notes)->toBe('Good performance.');
});
