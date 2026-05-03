<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\User\Models\User;
use App\Models\GeneratedReport;
use Illuminate\Database\Eloquent\Factories\Factory;

class GeneratedReportFactory extends Factory
{
    protected $model = GeneratedReport::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'user_id' => User::factory(),
            'report_type' => $this->faker->randomElement([
                'attendance_summary',
                'internship_placements',
                'student_performance',
                'company_overview',
            ]),
            'file_path' => 'reports/sample-report.pdf',
            'file_size' => $this->faker->numberBetween(10000, 5000000),
            'status' => 'completed',
            'filters' => ['date_from' => $this->faker->date()],
            'generated_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function pending(): static
    {
        return $this->state(
            fn (array $attributes) => [
                'status' => 'pending',
                'file_path' => null,
                'file_size' => null,
                'generated_at' => null,
            ],
        );
    }

    public function failed(): static
    {
        return $this->state(
            fn (array $attributes) => [
                'status' => 'failed',
                'error_message' => 'Report generation timed out.',
            ],
        );
    }
}
