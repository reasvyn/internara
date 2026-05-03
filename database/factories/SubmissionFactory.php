<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Assignment\Models\Assignment;
use App\Domain\Assignment\Models\Submission;
use App\Domain\Internship\Models\Registration;
use App\Domain\User\Models\User;
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
