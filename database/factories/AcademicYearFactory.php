<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\School\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

class AcademicYearFactory extends Factory
{
    protected $model = AcademicYear::class;

    public function definition(): array
    {
        $startYear = $this->faker->numberBetween(2020, 2025);
        $startDate = "$startYear-07-01";
        $endDate = $startYear + 1 .'-06-30';

        return [
            'id' => $this->faker->uuid(),
            'name' => "{$startYear}/".($startYear + 1),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_active' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => true]);
    }
}
