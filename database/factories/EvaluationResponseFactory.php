<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Evaluation\Models\EvaluationForm;
use App\Evaluation\Models\EvaluationResponse;
use App\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EvaluationResponseFactory extends Factory
{
    protected $model = EvaluationResponse::class;

    public function definition(): array
    {
        return [
            'form_id' => EvaluationForm::factory(),
            'evaluator_id' => User::factory(),
            'target_type' => 'mentor',
            'target_id' => User::factory(),
            'registration_id' => null,
            'overall_score' => fake()->randomFloat(1, 0, 100),
            'notes' => fake()->optional()->sentence(),
            'submitted_at' => now(),
        ];
    }
}
