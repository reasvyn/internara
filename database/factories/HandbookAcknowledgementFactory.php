<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Handbook;
use App\Models\HandbookAcknowledgement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class HandbookAcknowledgementFactory extends Factory
{
    protected $model = HandbookAcknowledgement::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'user_id' => User::factory(),
            'handbook_id' => Handbook::factory(),
            'acknowledged_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'ip_address' => $this->faker->ipv4(),
        ];
    }
}
