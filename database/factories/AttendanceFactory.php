<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enrollment\Models\Registration;
use App\Journals\Attendance\Enums\AttendanceStatus;
use App\Journals\Attendance\Models\Attendance;
use App\User\Models\User;
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
}
