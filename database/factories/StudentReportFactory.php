<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Reports\Domain\StudentReport\Models\StudentReport;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentReportFactory extends Factory
{
    protected $model = StudentReport::class;

    public function definition(): array
    {
        $supervisor = fake()->randomFloat(2, 60, 100);
        $teacher = fake()->randomFloat(2, 60, 100);
        $exam = fake()->randomFloat(2, 60, 100);
        $final = round(($supervisor + $teacher + $exam) / 3, 2);

        $gradeLetter = match (true) {
            $final >= 85 => 'A',
            $final >= 75 => 'B',
            $final >= 60 => 'C',
            default => 'D',
        };

        return [
            'registration_id' => Registration::factory(),
            'supervisor_score' => $supervisor,
            'teacher_score' => $teacher,
            'exam_score' => $exam,
            'final_score' => $final,
            'grade_letter' => $gradeLetter,
            'industry_feedback' => fake()->paragraph(),
            'status' => 'draft',
            'finalized_by' => null,
            'finalized_at' => null,
            'archived_data' => [
                'school_name' => fake()->company().' School',
                'finalized_by_name' => fake()->name(),
                'student_name' => fake()->name(),
                'student_number' => 'NIM-'.fake()->unique()->numberBetween(100000, 999999),
                'student_email' => fake()->safeEmail(),
                'internship_name' => fake()->sentence(3),
                'company_name' => fake()->company(),
                'department_name' => fake()->word().' Department',
                'supervisor_name' => fake()->name(),
                'teacher_name' => fake()->name(),
            ],
        ];
    }

    public function finalized(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StudentStudentReportStatus::FINALIZED,
            'finalized_by' => User::factory(),
            'finalized_at' => now(),
        ]);
    }
}
