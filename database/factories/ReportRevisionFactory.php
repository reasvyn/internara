<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Internship\Models\ReportRevision;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportRevisionFactory extends Factory
{
    protected $model = ReportRevision::class;

    public function definition(): array
    {
        return [
            'round' => 1,
            'feedback' => fake()->paragraph(),
        ];
    }
}
