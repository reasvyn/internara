<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Assignment\Models\AssignmentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for AssignmentType model.
 */
class AssignmentTypeFactory extends Factory
{
    protected $model = AssignmentType::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'name' => $this->faker->words(2, true),
            'slug' => $this->faker->slug(2),
            'group' => $this->faker->randomElement(['academic', 'practical', 'report']),
            'description' => $this->faker->sentence(),
        ];
    }
}
