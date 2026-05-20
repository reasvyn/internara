<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Assessment\Models\Rubric;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RubricFactory extends Factory
{
    protected $model = Rubric::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'is_active' => true,
            'created_by' => User::factory(),
        ];
    }
}
