<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Certification\Certificate\Models\CertificateTemplate;
use App\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CertificateTemplateFactory extends Factory
{
    protected $model = CertificateTemplate::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'layout' => fake()->randomElement(['portrait', 'landscape']),
            'content_template' => '<html><body>{{ $studentName }}</body></html>',
            'is_active' => true,
            'created_by' => User::factory(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attrs) => ['is_active' => false]);
    }
}
