<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Internship;
use App\Models\InternshipPlacement;
use App\Models\InternshipRegistration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InternshipRegistration>
 */
class InternshipRegistrationFactory extends Factory
{
    protected $model = InternshipRegistration::class;

    public function definition(): array
    {
        return [
            'student_id' => User::factory(),
            'internship_id' => Internship::factory(),
            'placement_id' => null,
            'academic_year' => fake()->year() . '/' . (fake()->year() + 1),
            'start_date' => fake()->dateTimeBetween('+1 month', '+3 months'),
            'end_date' => fake()->dateTimeBetween('+4 months', '+6 months'),
        ];
    }
}
