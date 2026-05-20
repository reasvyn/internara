<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Guidance\Models\Handbook;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class HandbookFactory extends Factory
{
    protected $model = Handbook::class;

    public function definition(): array
    {
        $title = $this->faker->sentence(4);

        return [
            'id' => $this->faker->uuid(),
            'title' => $title,
            'slug' => str($title)->slug(),
            'content' => $this->faker->paragraphs(5, true),
            'version' => $this->faker->numberBetween(1, 5),
            'is_active' => true,
            'published_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'created_by' => User::factory(),
        ];
    }

    public function draft(): static
    {
        return $this->state(
            fn (array $attributes) => [
                'is_active' => false,
                'published_at' => null,
            ],
        );
    }

    public function published(): static
    {
        return $this->state(
            fn (array $attributes) => [
                'is_active' => true,
                'published_at' => now(),
            ],
        );
    }
}
