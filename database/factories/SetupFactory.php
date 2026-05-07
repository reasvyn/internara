<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Setup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setup>
 */
class SetupFactory extends Factory
{
    protected $model = Setup::class;

    public function definition(): array
    {
        return [
            'is_installed' => false,
            'setup_token' => null,
            'token_expires_at' => null,
            'completed_steps' => [],
            'school_id' => null,
            'department_id' => null,
        ];
    }

    public function installed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_installed' => true,
        ]);
    }

    public function withToken(): static
    {
        return $this->state(fn (array $attributes) => [
            'setup_token' => hash('sha256', 'test-token'),
            'token_expires_at' => now()->addHour(),
        ]);
    }
}
