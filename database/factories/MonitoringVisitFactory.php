<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Journals\Domain\MonitoringVisit\Enums\VisitMethod;
use App\Modules\Journals\Domain\MonitoringVisit\Models\MonitoringVisit;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MonitoringVisitFactory extends Factory
{
    protected $model = MonitoringVisit::class;

    public function definition(): array
    {
        return [
            'registration_id' => Registration::factory(),
            'teacher_id' => User::factory(),
            'visit_date' => now()->toDateString(),
            'method' => VisitMethod::SITE_VISIT,
            'location' => $this->faker->address(),
            'duration_minutes' => 60,
            'notes' => $this->faker->paragraph(),
            'is_verified' => false,
        ];
    }
}
