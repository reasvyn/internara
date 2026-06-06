<?php

declare(strict_types=1);

namespace Database\Factories;

use App\SysAdmin\Observability\GdprDeletionLog\Models\GdprDeletionLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class GdprDeletionLogFactory extends Factory
{
    protected $model = GdprDeletionLog::class;

    public function definition(): array
    {
        return [
            'user_id' => Str::uuid()->toString(),
            'metadata_snapshot' => [
                'name' => fake()->name(),
                'email' => fake()->safeEmail(),
                'username' => fake()->userName(),
            ],
        ];
    }
}
