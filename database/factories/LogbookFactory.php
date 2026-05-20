<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Logbook\Models\Logbook;
use App\Domain\Registration\Models\Registration;
use App\Domain\User\Models\User;
use App\Enums\Logbook\LogbookStatus;
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
}
