<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Internship\Models\BriefingAttendance;
use Illuminate\Database\Eloquent\Factories\Factory;

class BriefingAttendanceFactory extends Factory
{
    protected $model = BriefingAttendance::class;

    public function definition(): array
    {
        return [
            'attended' => fake()->boolean(80),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
