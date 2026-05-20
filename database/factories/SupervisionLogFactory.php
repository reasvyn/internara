<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Mentor\Models\SupervisionLog;
use App\Domain\Registration\Models\Registration;
use App\Domain\User\Models\User;
use App\Enums\Mentor\SupervisionLogStatus;
use App\Enums\Mentor\SupervisionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupervisionLog>
 */
class SupervisionLogFactory extends Factory
{
    protected $model = SupervisionLog::class;

    public function definition(): array
    {
        return [
            'registration_id' => Registration::factory(),
            'supervisor_id' => User::factory(),
            'type' => SupervisionType::MONITORING,
            'date' => now()->toDateString(),
            'topic' => $this->faker->sentence(),
            'notes' => $this->faker->paragraph(),
            'status' => SupervisionLogStatus::PENDING,
        ];
    }
}
