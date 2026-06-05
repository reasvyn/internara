<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enrollment\Models\Placement;
use App\Partners\Company\Models\Company;
use App\Program\Internship\Models\Internship;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Placement>
 */
class PlacementFactory extends Factory
{
    protected $model = Placement::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'internship_id' => Internship::factory(),
            'name' => fake()->jobTitle().' Intern',
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
