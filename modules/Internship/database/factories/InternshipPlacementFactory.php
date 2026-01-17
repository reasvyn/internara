<?php

declare(strict_types=1);

namespace Modules\Internship\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Internship\Models\InternshipPlacement;
use Modules\Internship\Services\Contracts\InternshipService;

class InternshipPlacementFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = InternshipPlacement::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'internship_id' => app(InternshipService::class)->factory(),
            'company_name' => $this->faker->company,
            'company_address' => $this->faker->address,
            'contact_person' => $this->faker->name,
            'contact_number' => $this->faker->phoneNumber,
            'slots' => 1,
        ];
    }
}
