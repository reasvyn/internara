<?php

declare(strict_types=1);

namespace Modules\Assignment\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Assignment\Models\Assignment;
use Modules\Assignment\Models\AssignmentType;

class AssignmentFactory extends Factory
{
    protected $model = Assignment::class;

    public function definition(): array
    {
        return [
            'assignment_type_id' => AssignmentType::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'is_mandatory' => true,
            'academic_year' => '2025/2026',
        ];
    }
}
