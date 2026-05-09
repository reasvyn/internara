<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Logbook\LogbookStatus;
use App\Models\Logbook;
use App\Models\Registration;
use App\Models\User;
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
