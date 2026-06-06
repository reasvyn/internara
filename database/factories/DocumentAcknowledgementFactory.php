<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Document\Models\Document;
use App\Document\Models\DocumentAcknowledgement;
use App\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentAcknowledgementFactory extends Factory
{
    protected $model = DocumentAcknowledgement::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'document_id' => Document::factory(),
            'acknowledged_at' => now(),
            'ip_address' => fake()->ipv4(),
        ];
    }
}
