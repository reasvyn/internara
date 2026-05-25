<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Internship\Models\InternshipDocumentRequirement;
use App\Domain\Registration\Models\Registration;
use App\Domain\Registration\Models\RegistrationDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

class RegistrationDocumentFactory extends Factory
{
    protected $model = RegistrationDocument::class;

    public function definition(): array
    {
        return [
            'registration_id' => Registration::factory(),
            'internship_document_requirement_id' => InternshipDocumentRequirement::factory(),
            'status' => 'pending',
            'admin_notes' => fake()->optional()->sentence(),
        ];
    }
}
