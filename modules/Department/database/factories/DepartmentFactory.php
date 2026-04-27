<?php

declare(strict_types=1);

namespace Modules\Department\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Department\Models\Department;
use Modules\School\Services\Contracts\SchoolService;

class DepartmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Department::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'name' => $this->faker->unique()->sentence(2),
            'description' => $this->faker->paragraph(3),
            'school_id' => app(SchoolService::class)->factory(),
        ];
    }
}
