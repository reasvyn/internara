<?php

declare(strict_types=1);

namespace Modules\Attendance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Attendance\Models\AttendanceLog;
use Modules\Internship\Models\InternshipRegistration;
use Modules\User\Models\User;

class AttendanceLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AttendanceLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'registration_id' => InternshipRegistration::factory(),
            'student_id' => User::factory(),
            'date' => now()->format('Y-m-d'),
            'check_in_at' => now(),
            'check_out_at' => null,
        ];
    }
}
