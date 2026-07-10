<?php

declare(strict_types=1);

use App\Evaluation\Models\EvaluationAnswer;
use App\Evaluation\Models\EvaluationQuestion;
use App\Evaluation\Models\EvaluationResponse;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('evaluation answer factory creates valid model', function () {
    $answer = EvaluationAnswer::factory()->create();

    expect($answer)->toBeInstanceOf(EvaluationAnswer::class);
    expect($answer->response_id)->not->toBeNull();
    expect($answer->question_id)->not->toBeNull();
    expect($answer->value)->not->toBeNull();
});

test('evaluation answer belongs to response', function () {
    $response = EvaluationResponse::factory()->create();
    $answer = EvaluationAnswer::factory()->create(['response_id' => $response->id]);

    expect($answer->response)->toBeInstanceOf(EvaluationResponse::class);
    expect($answer->response->id)->toBe($response->id);
});

test('evaluation answer belongs to question', function () {
    $question = EvaluationQuestion::factory()->create();
    $answer = EvaluationAnswer::factory()->create(['question_id' => $question->id]);

    expect($answer->question)->toBeInstanceOf(EvaluationQuestion::class);
    expect($answer->question->id)->toBe($question->id);
});

test('evaluation answer casts score to float', function () {
    $answer = EvaluationAnswer::factory()->create(['score' => 87.5]);

    expect($answer->score)->toBe(87.5);
    expect($answer->score)->toBeFloat();
});

test('evaluation answer fillable attributes are mass assignable', function () {
    $answer = EvaluationAnswer::factory()->create([
        'value' => '4',
        'score' => 80.0,
    ]);

    expect($answer->value)->toBe('4');
    expect($answer->score)->toBe(80.0);
});
