<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Attendance\AbsenceReasonType;
use App\Enums\Attendance\AbsenceRequestStatus;
use App\Models\AbsenceRequest;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AbsenceRequest>
 */
class AbsenceRequestFactory extends Factory
{
    protected $model = AbsenceRequest::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'registration_id' => Registration::factory(),
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'reason_type' => AbsenceReasonType::SICK,
            'reason_description' => $this->faker->sentence(),
            'status' => AbsenceRequestStatus::PENDING,
        ];
    }
}
