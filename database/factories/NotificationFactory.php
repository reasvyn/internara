<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for Notification model.
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement(['success', 'error', 'warning', 'info', 'system']),
            'title' => $this->faker->sentence(3),
            'message' => $this->faker->paragraph(),
            'data' => ['key' => 'value'],
            'link' => $this->faker->url(),
            'is_read' => $this->faker->boolean(20),
            'read_at' => function (array $attributes) {
                return $attributes['is_read'] ? now() : null;
            },
        ];
    }

    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => true,
            'read_at' => now(),
        ]);
    }
}
