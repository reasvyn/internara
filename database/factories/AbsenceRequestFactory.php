<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Attendance\Enums\AbsenceReasonType;
use App\Domain\Attendance\Enums\AbsenceRequestStatus;
use App\Domain\Attendance\Models\AbsenceRequest;
use App\Domain\Registration\Models\Registration;
use App\Domain\User\Models\User;
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
