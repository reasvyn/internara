<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Journals\Domain\SupervisionLog\Enums\SupervisionLogStatus;
use App\Modules\Journals\Domain\SupervisionLog\Enums\SupervisionType;
use App\Modules\Journals\Domain\SupervisionLog\Models\SupervisionLog;
use App\Modules\User\Models\User;
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
            'status' => SupervisionLogStatus::DRAFT,
        ];
    }
}
