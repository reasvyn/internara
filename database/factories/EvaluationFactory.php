<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Evaluation\Enums\EvaluationCategory;
use App\Domain\Evaluation\Models\Evaluation;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EvaluationFactory extends Factory
{
    protected $model = Evaluation::class;

    public function definition(): array
    {
        return [
            'evaluator_id' => User::factory(),
            'evaluation_type' => EvaluationCategory::MENTOR,
            'mentor_id' => User::factory(),
            'registration_id' => null,
            'overall_score' => $this->faker->randomFloat(1, 50, 100),
            'feedback' => $this->faker->optional()->paragraph(),
            'criteria_scores' => [
                'communication' => $this->faker->randomFloat(1, 50, 100),
                'responsiveness' => $this->faker->randomFloat(1, 50, 100),
                'guidance_quality' => $this->faker->randomFloat(1, 50, 100),
            ],
        ];
    }

    public function mentor(): static
    {
        return $this->state(fn () => [
            'evaluation_type' => EvaluationCategory::MENTOR,
        ]);
    }

    public function program(): static
    {
        return $this->state(fn () => [
            'evaluation_type' => EvaluationCategory::PROGRAM,
            'mentor_id' => null,
            'criteria_scores' => [
                'curriculum_relevance' => $this->faker->randomFloat(1, 50, 100),
                'administration' => $this->faker->randomFloat(1, 50, 100),
                'facility_support' => $this->faker->randomFloat(1, 50, 100),
            ],
        ]);
    }
}
