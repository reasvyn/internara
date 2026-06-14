<?php

declare(strict_types=1);

namespace Database\Factories;

use App\SysAdmin\Backups\Enums\BackupStatus;
use App\SysAdmin\Backups\Enums\BackupType;
use App\SysAdmin\Backups\Models\Backup;
use App\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

final class BackupFactory extends Factory
{
    protected $model = Backup::class;

    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(BackupType::cases())->value,
            'file_path' => 'backup/test_'.fake()->uuid().'.sql.gz',
            'file_size' => fake()->numberBetween(1024, 10485760),
            'status' => BackupStatus::COMPLETED->value,
            'created_by' => User::factory(),
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ];
    }

    public function database(): static
    {
        return $this->state(fn () => ['type' => BackupType::DATABASE->value]);
    }

    public function storage(): static
    {
        return $this->state(fn () => ['type' => BackupType::STORAGE->value]);
    }

    public function both(): static
    {
        return $this->state(fn () => ['type' => BackupType::BOTH->value]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => BackupStatus::FAILED->value,
            'error_output' => 'Simulated failure',
            'completed_at' => now(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => BackupStatus::PENDING->value,
            'started_at' => null,
            'completed_at' => null,
        ]);
    }
}
