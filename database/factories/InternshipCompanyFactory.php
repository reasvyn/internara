<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Internship\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

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
