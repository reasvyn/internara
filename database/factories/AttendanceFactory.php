<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Journals\Domain\Attendance\Enums\AttendanceStatus;
use App\Modules\Journals\Domain\Attendance\Models\Attendance;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'registration_id' => Registration::factory(),
            'date' => now()->toDateString(),
            'clock_in' => now()->subHours(8),
            'clock_out' => now(),
            'status' => AttendanceStatus::PRESENT,
        ];
    }

    public function late(): static
    {
        return $this->state(fn (array $attributes) => ['status' => AttendanceStatus::LATE]);
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
            'verified_by' => User::factory(),
            'verified_at' => now(),
        ]);
    }
}
