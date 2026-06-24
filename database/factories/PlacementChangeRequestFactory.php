<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enrollment\Placement\Models\Placement;
use App\Enrollment\Placement\Models\PlacementChangeRequest;
use App\Enrollment\Registration\Models\Registration;
use App\Program\Internship\Models\Internship;
use App\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlacementChangeRequestFactory extends Factory
{
    protected $model = PlacementChangeRequest::class;

    public function definition(): array
    {
        return [
            'registration_id' => Registration::factory(),
            'from_placement_id' => Placement::factory(),
            'to_placement_id' => Placement::factory(),
            'reason' => fake()->paragraph(),
            'requested_by' => User::factory(),
        ];
    }

    public function forRegistration(Registration $registration): static
    {
        return $this->state(fn (array $attrs) => [
            'registration_id' => $registration->id,
            'from_placement_id' => Placement::factory()->state([
                'internship_id' => $registration->internship_id,
            ]),
            'to_placement_id' => Placement::factory()->state([
                'internship_id' => $registration->internship_id,
            ]),
        ]);
    }
}
