<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<User>
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'username' => 'u'.$this->faker->unique()->numerify('########'),
            'password' => Hash::make('password'),
            'setup_required' => false,
        ];
    }

    /**
     * Indicate that the user requires setup.
     */
    public function requiresSetup(): static
    {
        return $this->state(
            fn () => [
                'setup_required' => true,
            ],
        );
    }
}
