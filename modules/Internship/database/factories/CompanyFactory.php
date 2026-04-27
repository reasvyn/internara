<?php

declare(strict_types=1);

namespace Modules\Internship\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Internship\Models\Company;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Company>
     */
    protected $model = Company::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'name' => fake()->company(),
            'address' => fake()->address(),
            'business_field' => fake()->jobTitle(),
            'phone' => fake()->phoneNumber(),
            'fax' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'leader_name' => fake()->name(),
        ];
    }
}
