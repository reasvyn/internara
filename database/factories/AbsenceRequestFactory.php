<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Journals\Domain\AbsenceRequest\Enums\AbsenceReasonType;
use App\Modules\Journals\Domain\AbsenceRequest\Enums\AbsenceRequestStatus;
use App\Modules\Journals\Domain\AbsenceRequest\Models\AbsenceRequest;
use App\Modules\User\Models\User;
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
