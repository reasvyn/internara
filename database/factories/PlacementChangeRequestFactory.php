<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Enrollment\Models\Placement;
use App\Domain\Enrollment\Models\PlacementChangeRequest;
use App\Domain\Enrollment\Models\Registration;
use App\Domain\Program\Aggregates\Internship\Models\Internship;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlacementChangeRequestFactory extends Factory
{
    protected $model = PlacementChangeRequest::class;

    public function definition(): array
    {
        $internship = Internship::factory()->create();
        $registration = Registration::factory()->create(['internship_id' => $internship->id]);

        return [
            'registration_id' => $registration->id,
            'from_placement_id' => Placement::factory()->create(['internship_id' => $internship->id])->id,
            'to_placement_id' => Placement::factory()->create(['internship_id' => $internship->id])->id,
            'reason' => fake()->paragraph(),
            'requested_by' => User::factory(),
        ];
    }
}
