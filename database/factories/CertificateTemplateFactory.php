<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Certificate\Models\CertificateTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class CertificateTemplateFactory extends Factory
{
    protected $model = CertificateTemplate::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'layout' => 'portrait',
            'content_template' => '<h1>{{student_name}}</h1>',
        ];
    }
}
