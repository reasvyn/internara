<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Academics\AcademicYear\Models\AcademicYear;
use App\Program\Internship\Models\Internship;
use Illuminate\Database\Eloquent\Factories\Factory;

class InternshipFactory extends Factory
{
    protected $model = Internship::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+1 month', '+3 months');

        return [
            'academic_year_id' => AcademicYear::factory(),
            'name' => fake()->unique()->sentence(3),
            'start_date' => $start,
            'end_date' => fake()->dateTimeBetween($start->format('Y-m-d'), '+6 months'),
            'description' => fake()->paragraph(),
            'status' => 'draft',
            'phases' => [
                [
                    'name' => 'Persiapan',
                    'start_date' => $start->format('Y-m-d'),
                    'end_date' => (clone $start)->modify('+1 month')->format('Y-m-d'),
                ],
            ],
            'required_document_ids' => [],
            'grading_weights' => [
                'assignments' => 40,
                'rubrics' => 60,
            ],
        ];
    }
}
