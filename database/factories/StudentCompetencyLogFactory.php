<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Competency;
use App\Models\Registration;
use App\Models\StudentCompetencyLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for StudentCompetencyLog model.
 */
class StudentCompetencyLogFactory extends Factory
{
    protected $model = StudentCompetencyLog::class;

    public function definition(): array
    {
        return [
            'registration_id' => Registration::factory(),
            'competency_id' => Competency::factory(),
            'evaluator_id' => User::factory(),
            'score' => $this->faker->randomFloat(2, 0, 100),
            'notes' => $this->faker->sentence(),
        ];
    }
}
