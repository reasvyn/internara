<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Journals\Domain\Logbook\Enums\LogbookStatus;
use App\Modules\Journals\Domain\Logbook\Models\Logbook;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Logbook>
 */
class LogbookFactory extends Factory
{
    protected $model = Logbook::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'registration_id' => Registration::factory(),
            'date' => now()->toDateString(),
            'content' => $this->faker->paragraph(),
            'status' => LogbookStatus::DRAFT,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => ['status' => LogbookStatus::SUBMITTED]);
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LogbookStatus::VERIFIED,
            'is_verified' => true,
            'verified_by' => User::factory(),
            'verified_at' => now(),
        ]);
    }
}
