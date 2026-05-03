<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Assessment\Models\Competency;
use App\Domain\Internship\Models\Registration;
use App\Domain\Mentee\Models\CompetencyLog;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for CompetencyLog model.
 */
class CompetencyLogFactory extends Factory
{
    protected $model = CompetencyLog::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'registration_id' => Registration::factory(),
            'competency_id' => Competency::factory(),
            'evaluator_id' => User::factory(),
            'score' => $this->faker->randomFloat(2, 0, 100),
            'notes' => $this->faker->sentence(),
        ];
    }
}
