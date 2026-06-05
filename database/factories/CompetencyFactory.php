<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Assessment\Rubric\Models\Competency;
use App\Assessment\Rubric\Models\Rubric;
use App\Evaluation\Core\Enums\EvaluatorRole;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompetencyFactory extends Factory
{
    protected $model = Competency::class;

    public function definition(): array
    {
        return [
            'rubric_id' => Rubric::factory(),
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'weight' => fake()->numberBetween(10, 50),
            'evaluator_role' => fake()->randomElement(EvaluatorRole::cases()),
            'order' => fake()->numberBetween(1, 10),
        ];
    }
}
