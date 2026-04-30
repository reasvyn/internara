<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Assessment;
use App\Models\InternshipRegistration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for Assessment model.
 */
class AssessmentFactory extends Factory
{
    protected $model = Assessment::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'registration_id' => InternshipRegistration::factory(),
            'academic_year' => $this->faker->year(),
            'evaluator_id' => User::factory(),
            'type' => $this->faker->randomElement(['midterm', 'final']),
            'score' => $this->faker->randomFloat(2, 0, 100),
            'content' => [
                ['criterion' => 'Technical Skills', 'score' => $this->faker->randomFloat(2, 0, 40)],
                ['criterion' => 'Soft Skills', 'score' => $this->faker->randomFloat(2, 0, 30)],
                ['criterion' => 'Attendance', 'score' => $this->faker->randomFloat(2, 0, 30)],
            ],
            'feedback' => $this->faker->sentence(),
            'finalized_at' => null,
        ];
    }

    public function finalized(): static
    {
        return $this->state(fn (array $attributes) => ['finalized_at' => now()]);
    }
}
