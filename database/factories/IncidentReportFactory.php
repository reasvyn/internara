<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Incident\Aggregates\IncidentReport\Models\IncidentReport;
use Illuminate\Database\Eloquent\Factories\Factory;

class IncidentReportFactory extends Factory
{
    protected $model = IncidentReport::class;

    public function definition(): array
    {
        return [
            'incident_date' => fake()->dateTimeBetween('-1 month'),
            'type' => 'other',
            'severity' => 'medium',
            'description' => fake()->paragraph(),
        ];
    }
}
