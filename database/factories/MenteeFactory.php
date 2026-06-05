<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Guidance\Mentee\Models\Mentee;
use App\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MenteeFactory extends Factory
{
    protected $model = Mentee::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'user_id' => User::factory(),
            'is_active' => true,
        ];
    }

    public function inactive(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
