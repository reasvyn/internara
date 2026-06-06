<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Assignment\Models\Assignment;
use App\Assignment\Submission\Models\Submission;
use App\Enrollment\Models\Registration;
use App\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for Submission model.
 */
class SubmissionFactory extends Factory
{
    protected $model = Submission::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'assignment_id' => Assignment::factory(),
            'registration_id' => Registration::factory(),
            'student_id' => User::factory(),
            'content' => $this->faker->paragraph(),
            'metadata' => ['file_name' => 'report.pdf'],
            'submitted_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'status' => 'submitted',
        ];
    }

    public function graded(?float $score = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'verified',
            'score' => $score ?? $this->faker->randomFloat(1, 70, 100),
            'feedback' => $this->faker->sentence(),
            'graded_by' => User::factory(),
            'graded_at' => now(),
        ]);
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'verified']);
    }

    public function draft(): static
    {
        return $this->state(
            fn (array $attributes) => [
                'status' => 'draft',
                'submitted_at' => null,
            ],
        );
    }
}
