<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enrollment\Registration\Models\Registration;
use App\Journals\IndustryAssessment\Models\IndustryAssessment;
use App\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IndustryAssessment>
 */
class IndustryAssessmentFactory extends Factory
{
    protected $model = IndustryAssessment::class;

    public function definition(): array
    {
        return [
            'registration_id' => Registration::factory(),
            'supervisor_id' => User::factory(),
            'score' => $this->faker->randomFloat(2, 0, 100),
            'rubric_data' => [],
            'notes' => $this->faker->sentence(),
            'submitted_at' => now(),
        ];
    }
}
