<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Auth\AccessTokens\Models\AccessToken;
use App\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccessToken>
 */
class AccessTokenFactory extends Factory
{
    protected $model = AccessToken::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'token' => hash('sha256', $this->faker->uuid()),
            'token_type' => 'activation',
            'name' => $this->faker->word(),
            'expires_at' => now()->addDays(30),
            'attempts' => 0,
        ];
    }
}
