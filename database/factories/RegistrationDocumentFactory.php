<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Registration\Models\RegistrationDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

class RegistrationDocumentFactory extends Factory
{
    protected $model = RegistrationDocument::class;

    public function definition(): array
    {
        return [
            'admin_notes' => fake()->optional()->sentence(),
        ];
    }
}
