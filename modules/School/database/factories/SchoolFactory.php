<?php

declare(strict_types=1);

namespace Modules\School\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SchoolFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\School\Models\School::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'institutional_code' => $this->faker->unique()->numerify('########'), // 8 digits
            'name' => $this->faker->company,
            'address' => $this->faker->address,
            'email' => $this->faker->unique()->companyEmail,
            'phone' => $this->faker->numerify('08##########'), // 12 digits
            'fax' => $this->faker->numerify('021#######'), // 10 digits
            'principal_name' => $this->faker->name,
        ];
    }
}
