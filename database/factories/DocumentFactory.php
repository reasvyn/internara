<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Document\DocumentCategory;
use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'slug' => fake()->unique()->slug(3),
            'category' => fake()->randomElement(DocumentCategory::cases()),
            'description' => fake()->sentence(),
            'content' => fake()->optional()->paragraphs(3, true),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
