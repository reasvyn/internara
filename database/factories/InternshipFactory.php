<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Internship;
use Illuminate\Database\Eloquent\Factories\Factory;

class InternshipFactory extends Factory
{
    protected $model = Internship::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+1 month', '+3 months');
        $regStart = fake()->dateTimeBetween('-1 month', 'now');
        $regEnd = fake()->dateTimeBetween('+1 week', '+1 month');

        return [
            'academic_year_id' => AcademicYear::factory(),
            'name' => fake()->unique()->sentence(3),
            'start_date' => $start,
            'end_date' => fake()->dateTimeBetween($start->format('Y-m-d'), '+6 months'),
            'registration_start_date' => $regStart,
            'registration_end_date' => $regEnd,
            'description' => fake()->paragraph(),
            'status' => 'draft',
        ];
    }
}
