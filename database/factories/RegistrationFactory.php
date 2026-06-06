<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enrollment\Models\Registration;
use App\Program\Internship\Models\Internship;
use App\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RegistrationFactory extends Factory
{
    protected $model = Registration::class;

    public function definition(): array
    {
        return [
            'student_id' => User::factory(),
            'internship_id' => Internship::factory(),
            'placement_id' => null,
            'start_date' => fake()->dateTimeBetween('+1 month', '+3 months'),
            'end_date' => fake()->dateTimeBetween('+4 months', '+6 months'),
            'status' => 'pending',
            'proposed_company_details' => [
                'company_name' => fake()->company(),
                'address' => fake()->address(),
                'contact_email' => fake()->companyEmail(),
                'contact_phone' => fake()->phoneNumber(),
            ],
        ];
    }
}
