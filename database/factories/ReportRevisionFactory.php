<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Reports\Report\Models\Report;
use App\Reports\Report\Models\ReportRevision;
use App\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportRevisionFactory extends Factory
{
    protected $model = ReportRevision::class;

    public function definition(): array
    {
        return [
            'report_id' => Report::factory(),
            'round' => fake()->numberBetween(1, 5),
            'feedback' => fake()->paragraph(),
            'requested_by' => User::factory(),
            'requested_at' => now(),
            'resubmitted_at' => null,
        ];
    }
}
