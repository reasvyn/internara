<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Academics\Department\Models\Department;
use App\Academics\School\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word().' Department',
            'school_id' => School::factory(),
        ];
    }
}
