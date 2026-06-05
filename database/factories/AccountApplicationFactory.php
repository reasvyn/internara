<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enrollment\Models\AccountApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountApplicationFactory extends Factory
{
    protected $model = AccountApplication::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'status' => 'pending',
            'internship_id' => InternshipFactory::new(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attrs) => ['status' => 'pending']);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attrs) => ['status' => 'approved']);
    }
}
