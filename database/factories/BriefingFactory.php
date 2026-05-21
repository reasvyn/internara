<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Internship\Models\Briefing;
use App\Domain\Internship\Models\Internship;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BriefingFactory extends Factory
{
    protected $model = Briefing::class;

    public function definition(): array
    {
        return [
            'internship_id' => Internship::factory(),
            'created_by' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'date' => fake()->dateTimeBetween('+1 week', '+1 month'),
            'location' => fake()->optional()->city(),
        ];
    }
}
