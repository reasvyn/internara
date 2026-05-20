<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Internship\Models\Internship;
use App\Domain\Schedule\Models\Schedule;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduleFactory extends Factory
{
    protected $model = Schedule::class;

    public function definition(): array
    {
        $startAt = $this->faker->dateTimeBetween('now', '+3 months');
        $endAt = (clone $startAt)->modify('+2 hours');

        return [
            'id' => $this->faker->uuid(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'start_at' => $startAt,
            'end_at' => $endAt,
            'type' => $this->faker->randomElement([
                'orientation',
                'workshop',
                'evaluation',
                'visit',
                'presentation',
            ]),
            'location' => $this->faker->city(),
            'internship_id' => Internship::factory(),
            'created_by' => User::factory(),
        ];
    }

    public function orientation(): static
    {
        return $this->state(fn (array $attributes) => ['type' => 'orientation']);
    }

    public function workshop(): static
    {
        return $this->state(fn (array $attributes) => ['type' => 'workshop']);
    }
}
