<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Document\Models\Document;
use App\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(['template', 'policy', 'guideline']),
            'slug' => fake()->unique()->slug(3),
            'title' => fake()->unique()->words(3, true),
            'content' => fake()->optional()->paragraphs(3, true),
            'file_path' => null,
            'version' => 1,
            'is_active' => true,
            'metadata' => [
                'department' => fake()->word(),
                'tags' => [fake()->word(), fake()->word()],
            ],
            'created_by' => User::factory(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
