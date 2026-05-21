<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Assessment\Models\PresentationExaminer;
use Illuminate\Database\Eloquent\Factories\Factory;

class PresentationExaminerFactory extends Factory
{
    protected $model = PresentationExaminer::class;

    public function definition(): array
    {
        return [
            'score' => fake()->randomFloat(2, 50, 100),
            'feedback' => fake()->optional()->paragraph(),
        ];
    }
}
