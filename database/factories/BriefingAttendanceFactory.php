<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Internship\Models\Briefing;
use App\Domain\Internship\Models\BriefingAttendance;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BriefingAttendanceFactory extends Factory
{
    protected $model = BriefingAttendance::class;

    public function definition(): array
    {
        return [
            'briefing_id' => Briefing::factory(),
            'user_id' => User::factory(),
            'attended' => fake()->boolean(80),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
