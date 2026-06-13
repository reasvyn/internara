<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Evaluation\Models\EvaluationForm;
use App\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EvaluationFormFactory extends Factory
{
    protected $model = EvaluationForm::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'target_type' => fake()->randomElement(['mentor', 'program', 'company']),
            'is_active' => true,
            'created_by' => User::factory(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attrs) => ['is_active' => false]);
    }
}
