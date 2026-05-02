<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Internship;
use App\Models\InternshipCompany;
use App\Models\InternshipPlacement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InternshipPlacement>
 */
class InternshipPlacementFactory extends Factory
{
    protected $model = InternshipPlacement::class;

    public function definition(): array
    {
        return [
            'company_id' => InternshipCompany::factory(),
            'internship_id' => Internship::factory(),
            'name' => fake()->jobTitle() . ' Intern',
            'address' => fake()->optional()->address(),
            'quota' => fake()->numberBetween(5, 50),
            'filled_quota' => 0,
            'description' => fake()->paragraph(),
        ];
    }

    public function withFilledQuota(int $filled): static
    {
        return $this->state(function (array $attributes) use ($filled) {
            return [
                'filled_quota' => $filled,
            ];
        });
    }

    public function full(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'filled_quota' => $attributes['quota'] ?? 10,
            ];
        });
    }
}
