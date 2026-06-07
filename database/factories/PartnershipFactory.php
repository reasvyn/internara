<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Partners\Partnership\Models\Partnership;
use Illuminate\Database\Eloquent\Factories\Factory;

class PartnershipFactory extends Factory
{
    protected $model = Partnership::class;

    public function definition(): array
    {
        return [
            'agreement_number' => 'MOU-'.fake()->unique()->year().'-'.fake()->unique()->numberBetween(100, 999),
            'title' => fake()->sentence(4),
            'start_date' => fake()->date(),
            'end_date' => fake()->dateTimeBetween('+6 months', '+2 years')->format('Y-m-d'),
            'scope' => fake()->optional()->paragraph(),
            'contact_person_name' => fake()->name(),
            'contact_person_phone' => fake()->optional()->phoneNumber(),
            'contact_person_email' => fake()->optional()->companyEmail(),
            'signed_by_school' => fake()->name(),
            'signed_by_company' => fake()->name(),
            'signed_at' => fake()->optional()->date(),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => 'active']);
    }

    public function expired(): static
    {
        return $this->state(
            fn () => [
                'status' => 'expired',
                'end_date' => fake()->pastDay()->format('Y-m-d'),
            ],
        );
    }
}
