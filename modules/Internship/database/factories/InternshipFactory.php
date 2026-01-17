<?php

declare(strict_types=1);

namespace Modules\Internship\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\School\Services\Contracts\SchoolService;

class InternshipFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Internship\Models\Internship::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->unique()->sentence(3),
            'description' => $this->faker->paragraph,
            'year' => (int) date('Y'),
            'semester' => 'Ganjil',
            'date_start' => now()->toDateString(),
            'date_finish' => now()->addMonths(3)->toDateString(),
            'school_id' => app(SchoolService::class)->factory(),
        ];
    }
}
