<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enrollment\Registration\Models\Registration;
use App\Journals\AbsenceRequest\Enums\AbsenceReasonType;
use App\Journals\AbsenceRequest\Enums\AbsenceRequestStatus;
use App\Journals\AbsenceRequest\Models\AbsenceRequest;
use App\User\Models\User;
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
            'date' => now()->toDateString(),
            'absence_type' => AbsenceReasonType::SICK,
            'absence_reason' => $this->faker->sentence(),
            'absence_status' => AbsenceRequestStatus::PENDING,
        ];
    }
}
