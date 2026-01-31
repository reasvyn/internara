<?php

declare(strict_types=1);

namespace Modules\Schedule\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Schedule\Models\Schedule;

class ScheduleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Schedule::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph,
            'start_at' => now()->addDays(rand(1, 30)),
            'type' => $this->faker->randomElement(['event', 'deadline', 'briefing']),
            'academic_year' => '2025/2026',
        ];
    }
}
