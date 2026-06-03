<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Program\Aggregates\Internship\Models\Internship;
use App\Domain\Program\Aggregates\Internship\Models\InternshipPhase;
use Illuminate\Database\Eloquent\Factories\Factory;

class InternshipPhaseFactory extends Factory
{
    protected $model = InternshipPhase::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'description' => fake()->sentence(),
            'start_date' => now()->addDays(7),
            'end_date' => now()->addDays(21),
            'color' => fake()->hexColor(),
            'internship_id' => Internship::factory(),
            'order' => 1,
        ];
    }
}
