<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Assessment\Models\Competency;
use App\Domain\School\Models\DepartmentCompetency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for DepartmentCompetency model.
 */
class DepartmentCompetencyFactory extends Factory
{
    protected $model = DepartmentCompetency::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'department_id' => Department::factory(),
            'competency_id' => Competency::factory(),
            'is_active' => true,
        ];
    }
}
