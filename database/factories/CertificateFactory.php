<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Certification\Aggregates\Certificate\Models\Certificate;
use Illuminate\Database\Eloquent\Factories\Factory;

class CertificateFactory extends Factory
{
    protected $model = Certificate::class;

    public function definition(): array
    {
        return [
            'certificate_number' => 'CERT-'.fake()->unique()->numberBetween(1000, 9999),
        ];
    }
}
