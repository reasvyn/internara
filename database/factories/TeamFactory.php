<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Mentor\Models\Team;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'name' => $this->faker->company(),
            'description' => $this->faker->optional()->sentence(),
            'owner_id' => User::factory(),
            'is_active' => true,
        ];
    }
}
