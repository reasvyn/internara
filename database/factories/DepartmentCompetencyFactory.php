<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Competency;
use App\Models\Department;
use App\Models\DepartmentCompetency;
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
            'department_id' => Department::factory(),
            'competency_id' => Competency::factory(),
            'is_active' => true,
        ];
    }
}
