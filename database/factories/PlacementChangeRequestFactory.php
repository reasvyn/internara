<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Placement\Models\PlacementChangeRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlacementChangeRequestFactory extends Factory
{
    protected $model = PlacementChangeRequest::class;

    public function definition(): array
    {
        return [
            'reason' => fake()->paragraph(),
        ];
    }
}
