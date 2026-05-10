<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Document;
use App\Models\Internship;
use App\Models\InternshipDocumentRequirement;
use Illuminate\Database\Eloquent\Factories\Factory;

class InternshipDocumentRequirementFactory extends Factory
{
    protected $model = InternshipDocumentRequirement::class;

    public function definition(): array
    {
        return [
            'internship_id' => Internship::factory(),
            'document_id' => Document::factory(),
            'is_mandatory' => true,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
