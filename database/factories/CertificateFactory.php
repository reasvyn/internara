<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Certification\Certificate\Models\Certificate;
use App\Enrollment\Registration\Models\Registration;
use App\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CertificateFactory extends Factory
{
    protected $model = Certificate::class;

    public function definition(): array
    {
        return [
            'registration_id' => Registration::factory(),
            'certificate_number' => 'CERT-'.fake()->unique()->numberBetween(1000, 9999),
            'qr_hash' => fake()->unique()->sha256(),
            'status' => 'issued',
            'template_content' => '<h1>Certificate of Completion</h1><p>Awarded to {{ student_name }}</p>',
            'issued_by' => User::factory(),
            'issued_at' => now(),
        ];
    }
}
