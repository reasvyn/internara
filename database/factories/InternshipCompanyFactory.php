<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\InternshipCompany;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InternshipCompany>
 */
class InternshipCompanyFactory extends Factory
{
    protected $model = InternshipCompany::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'website' => fake()->optional()->url(),
            'description' => fake()->paragraph(),
            'industry_sector' => fake()->randomElement([
                'Technology',
                'Finance',
                'Healthcare',
                'Education',
                'Manufacturing',
                'Retail',
                'Government',
                'General',
            ]),
        ];
    }
}
