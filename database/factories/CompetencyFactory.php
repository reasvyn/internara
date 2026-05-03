<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Assessment\Models\Competency;
use App\Domain\School\Models\Department;
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
            'max_score' => 100.0,
            'weight' => 1.0,
        ];
    }
}
