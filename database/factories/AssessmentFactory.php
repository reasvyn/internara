<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Assessment\Models\Assessment;
use App\Enrollment\Models\Registration;
use App\User\Models\User;
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
            'registration_id' => Registration::factory(),
            'evaluator_id' => User::factory(),
            'assessment_type' => $this->faker->randomElement([
                'midterm',
                'final',
                'periodic',
                'industry',
            ]),
            'score' => $this->faker->randomFloat(2, 0, 100),
            'scores_data' => [
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
