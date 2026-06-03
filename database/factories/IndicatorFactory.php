<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Assessment\Aggregates\Rubric\Models\Competency;
use App\Domain\Assessment\Aggregates\Rubric\Models\Indicator;
use Illuminate\Database\Eloquent\Factories\Factory;

class IndicatorFactory extends Factory
{
    protected $model = Indicator::class;

    public function definition(): array
    {
        return [
            'competency_id' => Competency::factory(),
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'max_score' => 100,
            'weight' => fake()->numberBetween(10, 50),
            'order' => fake()->numberBetween(1, 10),
        ];
    }
}
