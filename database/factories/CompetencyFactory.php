<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Competency;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for Competency model.
 */
class CompetencyFactory extends Factory
{
    protected $model = Competency::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'department_id' => Department::factory(),
            'name' => $this->faker->words(2, true),
            'code' => strtoupper($this->faker->lexify('???')),
            'description' => $this->faker->sentence(),
            'max_score' => 100.00,
            'weight' => 1.00,
        ];
    }
}
