<?php

declare(strict_types=1);

namespace App\Setup\Installation\Support;

use Database\Seeders\SetupSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use RuntimeException;

class SystemProvisioner
{
    public function getTasks(): array
    {
        return [
            'ensure_env' => __('setup.cli.tasks.ensure_env'),
            'generate_key' => __('setup.cli.tasks.generate_key'),
            'run_migrations' => __('setup.cli.tasks.run_migrations'),
            'run_seeders' => __('setup.cli.tasks.run_seeders'),
            'storage_link' => __('setup.cli.tasks.storage_link'),
            'clear_cache' => __('setup.cli.tasks.clear_cache'),
        ];
    }

    public function executeTask(string $task, bool $force = false): void
    {
        match ($task) {
            'ensure_env' => $this->ensureEnvFile(),
            'generate_key' => $this->generateAppKey(),
            'run_migrations' => $this->runMigrations($force),
            'run_seeders' => $this->runSeeders(),
            'storage_link' => $this->createStorageSymlink(),
            'clear_cache' => $this->clearCaches(),
            default => throw new \InvalidArgumentException("Unknown provisioning task: {$task}"),
        };
    }

    public function executeAll(bool $force = false): void
    {
        foreach (array_keys($this->getTasks()) as $task) {
            $this->executeTask($task, $force);
        }
    }

    private function ensureEnvFile(): void
    {
        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            $examplePath = base_path('.env.example');

            if (! File::exists($examplePath)) {
                throw new RuntimeException('.env.example file not found');
            }

            File::copy($examplePath, $envPath);
            chmod($envPath, 0600);
        }
    }

    private function generateAppKey(): void
    {
        $envPath = base_path('.env');
        $envContent = File::get($envPath);

        if (! str_contains($envContent, 'APP_KEY=') || empty(config('app.key'))) {
            Artisan::call('key:generate');
        }
    }

    private function runMigrations(bool $force): void
    {
        $exitCode = $force
            ? Artisan::call('migrate:fresh', ['--force' => true])
            : Artisan::call('migrate', ['--force' => true]);

        if ($exitCode !== 0) {
            throw new RuntimeException('Migration failed');
        }
    }

    private function runSeeders(): void
    {
        $seeder = app(SetupSeeder::class);
        $seeder->run();
    }

    private function createStorageSymlink(): void
    {
        if (! file_exists(public_path('storage'))) {
            $exitCode = Artisan::call('storage:link');

            if ($exitCode !== 0) {
                throw new RuntimeException('Storage symlink creation failed');
            }
        }
    }

    private function clearCaches(): void
    {
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
    }
}
