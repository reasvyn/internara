<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Admin\Aggregates\Setup\Models\Setup;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

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
            'recovery_key' => null,
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

    public function withRecoveryKey(): static
    {
        return $this->state(fn (array $attributes) => [
            'recovery_key' => Hash::make('admin-recovery-key-2026'),
        ]);
    }
}
