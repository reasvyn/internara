<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Assessment\Models\Assessment;
use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RegistrationFactory extends Factory
{
    protected $model = Registration::class;

    public function definition(): array
    {
        return [
            'student_id' => User::factory(),
            'internship_id' => Internship::factory(),
            'placement_id' => null,
            'start_date' => fake()->dateTimeBetween('+1 month', '+3 months'),
            'end_date' => fake()->dateTimeBetween('+4 months', '+6 months'),
            'status' => 'pending',
            'proposed_company_details' => [
                'company_name' => fake()->company(),
                'address' => fake()->address(),
                'contact_email' => fake()->companyEmail(),
                'contact_phone' => fake()->phoneNumber(),
            ],
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'active']);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'pending']);
    }

    /**
     * Attach a placement to the registration — the placement gate of FR-CERT-010.
     */
    public function placed(): static
    {
        return $this->afterCreating(function (Registration $registration): void {
            $placement = Placement::factory()->create([
                'internship_id' => $registration->internship_id,
            ]);

            $registration->forceFill(['placement_id' => $placement->id])->saveQuietly();
        });
    }

    /**
     * Attach an assessment — finalized by default, or still open for the gate's
     * negative case.
     */
    public function assessed(bool $finalized = true): static
    {
        return $this->afterCreating(function (Registration $registration) use ($finalized): void {
            Assessment::factory()->create([
                'registration_id' => $registration->id,
                'finalized_at' => $finalized ? now() : null,
            ]);
        });
    }

    /**
     * A registration that clears the certificate eligibility gates (FR-CERT-010):
     * active, placed, and assessed with a finalized final assessment.
     *
     * The handbook gate is left to the caller — it depends on whether the school
     * published a handbook at all.
     */
    public function eligible(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'active'])
            ->placed()
            ->assessed();
    }
}
