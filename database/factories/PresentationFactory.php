<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Assessment\Models\Presentation;
use Illuminate\Database\Eloquent\Factories\Factory;

class PresentationFactory extends Factory
{
    protected $model = Presentation::class;

    public function definition(): array
    {
        return [
            'scheduled_at' => fake()->dateTimeBetween('+1 week', '+2 months'),
            'location' => fake()->optional()->city(),
        ];
    }
}
