<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use App\Modules\Enrollment\Domain\Placement\Models\PlacementChangeRequest;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\User\Models\User;
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
