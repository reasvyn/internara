<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Document\Models\Document;
use App\Enrollment\Models\Registration;
use App\Enrollment\Models\RegistrationDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

class RegistrationDocumentFactory extends Factory
{
    protected $model = RegistrationDocument::class;

    public function definition(): array
    {
        return [
            'registration_id' => Registration::factory(),
            'document_id' => Document::factory(),
            'status' => 'pending',
            'admin_notes' => fake()->optional()->sentence(),
        ];
    }
}
