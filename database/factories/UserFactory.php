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
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'username' => 'u'.$this->faker->unique()->numerify('########'),
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'setup_required' => false,
            'locked_at' => null,
            'locked_reason' => null,
        ];
    }

    public function requiresSetup(): static
    {
        return $this->state(fn () => ['setup_required' => true]);
    }

    public function locked(string $reason = 'too_many_failed_attempts'): static
    {
        return $this->state(fn () => [
            'locked_at' => now(),
            'locked_reason' => $reason,
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    public function withPassword(string $password): static
    {
        return $this->state(fn () => ['password' => Hash::make($password)]);
    }
}
