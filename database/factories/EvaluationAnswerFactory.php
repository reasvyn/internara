<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Evaluation\Models\EvaluationAnswer;
use App\Evaluation\Models\EvaluationQuestion;
use App\Evaluation\Models\EvaluationResponse;
use Illuminate\Database\Eloquent\Factories\Factory;

class EvaluationAnswerFactory extends Factory
{
    protected $model = EvaluationAnswer::class;

    public function definition(): array
    {
        return [
            'response_id' => EvaluationResponse::factory(),
            'question_id' => EvaluationQuestion::factory(),
            'value' => (string) fake()->numberBetween(1, 5),
            'score' => fake()->randomFloat(1, 0, 100),
        ];
    }
}
