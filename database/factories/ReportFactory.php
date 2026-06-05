<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enrollment\Models\Registration;
use App\Reports\Report\Models\Report;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportFactory extends Factory
{
    protected $model = Report::class;

    public function definition(): array
    {
        return [
            'registration_id' => Registration::factory(),
            'title' => fake()->sentence(5),
        ];
    }
}
