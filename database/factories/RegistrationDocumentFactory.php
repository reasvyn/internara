<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Document\Models\Document;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Enrollment\Domain\Registration\Models\RegistrationDocument;
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
