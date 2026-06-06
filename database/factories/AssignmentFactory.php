<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Assignment\Models\Assignment;
use App\Document\Models\Document;
use App\Program\Internship\Models\Internship;
use App\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for Assignment model.
 */
class AssignmentFactory extends Factory
{
    protected $model = Assignment::class;

    public function definition(): array
    {
        return [
            'internship_id' => Internship::factory(),
            'document_id' => Document::factory(),
            'assignment_type' => $this->faker->randomElement(['project', 'report', 'essay']),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'due_date' => $this->faker->dateTimeBetween('+1 week', '+2 months'),
            'status' => 'draft',
            'created_by' => User::factory(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'published']);
    }
}
