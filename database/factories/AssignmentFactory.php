<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Assignment\Models\Assignment;
use App\Domain\Assignment\Models\AssignmentType;
use App\Domain\Internship\Models\Internship;
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
            'id' => $this->faker->uuid(),
            'assignment_type_id' => AssignmentType::factory(),
            'internship_id' => Internship::factory(),
            'academic_year' => $this->faker->year(),
            'title' => $this->faker->sentence(3),
            'group' => $this->faker->randomElement(['academic', 'practical']),
            'description' => $this->faker->paragraph(),
            'is_mandatory' => $this->faker->boolean(30),
            'due_date' => $this->faker->dateTimeBetween('+1 week', '+2 months'),
            'config' => ['allow_file_upload' => true],
            'status' => 'draft',
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'published']);
    }
}
