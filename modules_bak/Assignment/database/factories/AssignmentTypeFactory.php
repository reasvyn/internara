<?php

declare(strict_types=1);

namespace Modules\Assignment\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Assignment\Models\AssignmentType;

class AssignmentTypeFactory extends Factory
{
    protected $model = AssignmentType::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'slug' => $this->faker->unique()->slug(),
            'description' => $this->faker->sentence(),
        ];
    }
}
