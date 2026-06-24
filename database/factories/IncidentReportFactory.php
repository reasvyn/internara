<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enrollment\Registration\Models\Registration;
use App\Incident\IncidentReport\Models\IncidentReport;
use App\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class IncidentReportFactory extends Factory
{
    protected $model = IncidentReport::class;

    public function definition(): array
    {
        return [
            'registration_id' => Registration::factory(),
            'reported_by' => User::factory(),
            'incident_date' => fake()->dateTimeBetween('-1 month'),
            'type' => 'other',
            'severity' => 'medium',
            'description' => fake()->paragraph(),
        ];
    }
}
