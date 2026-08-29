<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

class InternshipGroupFactory extends Factory
{
    protected $model = InternshipGroup::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word().' Group',
            'internship_id' => Internship::factory(),
            'is_active' => true,
        ];
    }
}
