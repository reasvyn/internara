<?php

declare(strict_types=1);

namespace Modules\Internship\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Internship\Models\InternshipRegistration;
use Modules\Internship\Services\Contracts\InternshipPlacementService;
use Modules\Internship\Services\Contracts\InternshipService;
use Modules\User\Services\Contracts\UserService;

class InternshipRegistrationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = InternshipRegistration::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'internship_id' => app(InternshipService::class)->factory(),
            'placement_id' => app(InternshipPlacementService::class)->factory(),
            'student_id' => app(UserService::class)->factory(),
            'teacher_id' => app(UserService::class)->factory(),
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->addMonths(3)->endOfMonth(),
        ];
    }
}
