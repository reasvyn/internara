<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\School\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

class SchoolFactory extends Factory
{
    protected $model = School::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'institutional_code' => fake()->unique()->numerify('##########'),
            'address' => fake()->address(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
        ];
    }
}
