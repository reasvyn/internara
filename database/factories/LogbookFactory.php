<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enrollment\Registration\Models\Registration;
use App\Journals\Logbook\Enums\LogbookStatus;
use App\Journals\Logbook\Models\Logbook;
use App\User\Models\User;
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
