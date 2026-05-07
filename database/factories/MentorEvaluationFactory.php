<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MentorEvaluation;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for MentorEvaluation model.
 */
class MentorEvaluationFactory extends Factory
{
    protected $model = MentorEvaluation::class;

    public function definition(): array
    {
        return [
            'evaluator_id' => User::factory(),
            'mentor_id' => User::factory(),
            'registration_id' => Registration::factory(),
            'overall_score' => $this->faker->randomFloat(1, 50, 100),
            'feedback' => $this->faker->optional()->paragraph(),
            'criteria_scores' => [
                'communication' => $this->faker->randomFloat(1, 50, 100),
                'responsiveness' => $this->faker->randomFloat(1, 50, 100),
                'guidance_quality' => $this->faker->randomFloat(1, 50, 100),
            ],
        ];
    }
}
