<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Internship;
use App\Models\Mentee;
use App\Models\Registration;
use Illuminate\Database\Eloquent\Factories\Factory;

class RegistrationFactory extends Factory
{
    protected $model = Registration::class;

    public function definition(): array
    {
        return [
            'mentee_id' => Mentee::factory(),
            'internship_id' => Internship::factory(),
            'placement_id' => null,
            'academic_year' => fake()->year().'/'.(fake()->year() + 1),
            'start_date' => fake()->dateTimeBetween('+1 month', '+3 months'),
            'end_date' => fake()->dateTimeBetween('+4 months', '+6 months'),
            'status' => 'pending',
        ];
    }
}
