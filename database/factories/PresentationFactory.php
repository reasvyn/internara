<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Assessment\Aggregates\Presentation\Models\Presentation;
use App\Domain\Enrollment\Models\Registration;
use Illuminate\Database\Eloquent\Factories\Factory;

class PresentationFactory extends Factory
{
    protected $model = Presentation::class;

    public function definition(): array
    {
        return [
            'registration_id' => Registration::factory(),
            'scheduled_at' => fake()->dateTimeBetween('+1 week', '+2 months'),
            'location' => fake()->optional()->city(),
        ];
    }
}
