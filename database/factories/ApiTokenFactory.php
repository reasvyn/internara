<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Auth\ApiTokens\Models\ApiToken;
use App\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApiToken>
 */
class ApiTokenFactory extends Factory
{
    protected $model = ApiToken::class;

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
