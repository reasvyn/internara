<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Enrollment\Models\Registration;
use App\Domain\Guidance\Aggregates\SupervisionLog\Enums\SupervisionLogStatus;
use App\Domain\Guidance\Aggregates\SupervisionLog\Enums\SupervisionType;
use App\Domain\Guidance\Aggregates\SupervisionLog\Models\SupervisionLog;
use App\Domain\User\Models\User;
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
