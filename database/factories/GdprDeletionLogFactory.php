<?php

declare(strict_types=1);

namespace Database\Factories;

use App\SysAdmin\GdprDeletionLog\Models\GdprDeletionLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class GdprDeletionLogFactory extends Factory
{
    protected $model = GdprDeletionLog::class;

    public function definition(): array
    {
        return [
            'user_email' => fake()->safeEmail(),
            'deletion_type' => 'anonymization',
            'reason' => fake()->sentence(),
        ];
    }
}
