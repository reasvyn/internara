<?php

declare(strict_types=1);

namespace Modules\Profile\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Profile\Models\Profile;
use Modules\User\Services\Contracts\UserService;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Profile\Models\Profile>
 */
class ProfileFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Modules\Profile\Models\Profile>
     */
    protected $model = Profile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => app(UserService::class)->factory(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'gender' => fake()->randomElement(['male', 'female']),
            'blood_type' => fake()->randomElement(['A', 'B', 'AB', 'O']),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => fake()->phoneNumber(),
            'emergency_contact_address' => fake()->address(),
            'bio' => fake()->paragraph(),
        ];
    }
}
