<?php

declare(strict_types=1);

namespace App\SysAdmin\Observability\Console\Commands;

use App\Core\Support\CacheKeys;
use App\Core\Support\SmartLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class SystemHealthCommand extends Command
{
    protected $signature = 'system:health
        {--json : Output results as JSON}';

    protected $description = 'Perform a comprehensive system health check';

    public function handle(): int
    {
        try {
            $results = array_merge($this->environmentChecks(), [
                [__('setup.system.php_version'), ...$this->checkPhpVersion()],
                [__('setup.system.extensions'), ...$this->checkExtensions()],
                [__('setup.system.recommended_extensions'), ...$this->checkRecommendedExtensions()],
                [__('setup.system.php_memory'), ...$this->checkMemory()],
                [__('setup.system.database'), ...$this->checkDatabase()],
                [__('setup.system.migration_status'), ...$this->checkMigrations()],
                [__('setup.system.storage'), ...$this->checkStorage()],
                [__('setup.system.disk_space'), ...$this->checkDiskSpace()],
                [__('setup.system.queue'), ...$this->checkQueue()],
                [__('setup.system.cache'), ...$this->checkCache()],
                [__('setup.system.app_key'), ...$this->checkAppKey()],
                [__('setup.system.storage_link'), ...$this->checkStorageLink()],
                [__('setup.system.maintenance_mode'), ...$this->checkMaintenanceMode()],
            ]);

            if ($this->option('json')) {
                $this->line(json_encode($results, JSON_PRETTY_PRINT));

                return Command::SUCCESS;
            }

            $this->healthHeader();
            $this->table(
                [__('setup.system.service'), __('setup.system.status'), __('setup.system.details')],
                $results,
            );

            $hasFailures = collect($results)->contains(fn ($r) => $r[1] === 'FAIL');

            if ($hasFailures) {
                $this->error("\n".__('setup.system.health_failed'));

                return Command::FAILURE;
            }

            $this->info("\n".__('setup.system.health_passed'));

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            SmartLogger::error(__('setup.system.health_failed'))
                ->module('system')
                ->event('health.check.failed')
                ->withPayload(['error' => $e->getMessage()])
                ->save();

            $this->error("\n".__('setup.system.health_failed').': '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    private function environmentChecks(): array
    {
        $checks = [];

        $checks[] = [__('setup.system.environment'), ...$this->checkEnvironment()];

        $checks[] = [__('setup.system.setup_status'), ...$this->checkSetupStatus()];

        return $checks;
    }

    protected function healthHeader(): void
    {
        $this->newLine();
        $this->line(
            '  <fg=white;options=bold;bg=green> '.__('setup.system.health_header').' </>',
        );
        $this->newLine();
        $this->components->twoColumnDetail(__('setup.system.time'), now()->toDateTimeString());
        $this->components->twoColumnDetail(__('setup.system.environment'), app()->environment());
        $this->newLine();
    }

    protected function checkEnvironment(): array
    {
        $exists = File::exists(base_path('.env'));

        return [$exists ? 'OK' : 'FAIL', $exists ? '.env file detected' : '.env file is missing!'];
    }

    protected function checkSetupStatus(): array
    {
        try {
            $installed = DB::table('setups')->where('is_installed', true)->exists();

            if ($installed) {
                $setup = DB::table('setups')->where('is_installed', true)->first();

                $completed = isset($setup->completed_steps)
                    ? count(json_decode($setup->completed_steps, true) ?? [])
                    : 0;

                return ['OK', "System installed ({$completed} steps completed)"];
            }

            return ['WARN', 'System not installed — run setup:install then visit /setup'];
        } catch (\Throwable) {
            return ['WARN', 'Setup table not available — system not initialized'];
        }
    }

    protected function checkPhpVersion(): array
    {
        $required = config('setup.requirements.php_version', '8.4.0');
        $current = PHP_VERSION;

        if (version_compare($current, $required, '>=')) {
            return ['OK', "PHP {$current} (required: {$required}+)"];
        }

        return ['FAIL', "PHP {$current} is below required {$required}"];
    }

    protected function checkExtensions(): array
    {
        $required = config('setup.requirements.extensions', [
            'bcmath',
            'ctype',
            'fileinfo',
            'mbstring',
            'openssl',
            'pdo',
            'tokenizer',
            'xml',
            'curl',
            'gd',
            'intl',
            'zip',
        ]);

        $missing = array_filter($required, fn ($ext) => ! extension_loaded($ext));

        if ($missing === []) {
            return ['OK', 'All '.count($required).' required extensions loaded'];
        }

        return ['FAIL', 'Missing: '.implode(', ', $missing)];
    }

    protected function checkRecommendedExtensions(): array
    {
        $recommended = config('setup.requirements.recommended_extensions', [
            'redis',
            'pcntl',
            'posix',
        ]);

        $missing = array_filter($recommended, fn ($ext) => ! extension_loaded($ext));

        if ($missing === []) {
            return ['OK', 'All '.count($recommended).' recommended extensions loaded'];
        }

        return ['WARN', 'Missing: '.implode(', ', $missing)];
    }

    protected function checkMemory(): array
    {
        $limit = ini_get('memory_limit');

        if ($limit === '-1') {
            return ['OK', 'Unlimited'];
        }

        $limitBytes = $this->memoryInBytes($limit);

        if ($limitBytes >= 128 * 1024 * 1024) {
            return ['OK', "{$limit} (minimum 128M met)"];
        }

        return ['WARN', "{$limit} may be low for batch operations (recommended: 256M)"];
    }

    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            $driver = DB::connection()->getDriverName();
            $tables = Schema::getTables();

            return ['OK', "{$driver} — connected, ".count($tables).' tables'];
        } catch (\Throwable $e) {
            return ['FAIL', 'Connection failed: '.$e->getMessage()];
        }
    }

    protected function checkMigrations(): array
    {
        try {
            if (! Schema::hasTable('migrations')) {
                return ['WARN', 'Migrations table not found — run migrations first'];
            }

            $migrationFiles = collect(File::files(database_path('migrations')))
                ->map(fn ($f) => $f->getFilename())
                ->filter(fn ($name) => str_ends_with($name, '.php'))
                ->map(fn ($name) => preg_replace('/\.php$/', '', $name))
                ->values();

            $runMigrations = DB::table('migrations')->pluck('migration');

            $pending = $migrationFiles->diff($runMigrations);

            if ($pending->isEmpty()) {
                return ['OK', 'All '.$migrationFiles->count().' migrations up to date'];
            }

            $count = $pending->count();

            return ['WARN', "{$count} pending migration(s) — run php artisan migrate"];
        } catch (\Throwable $e) {
            return ['WARN', 'Could not check migration status: '.$e->getMessage()];
        }
    }

    protected function checkStorage(): array
    {
        $paths = [
            storage_path('framework/views'),
            storage_path('framework/cache'),
            storage_path('logs'),
        ];

        $unwritable = array_filter($paths, fn ($p) => ! File::isWritable($p));

        if ($unwritable === []) {
            return ['OK', 'All storage directories are writable'];
        }

        return ['FAIL', 'Unwritable: '.implode(', ', $unwritable)];
    }

    protected function checkDiskSpace(): array
    {
        $path = storage_path();
        $free = disk_free_space($path);
        $total = disk_total_space($path);
        $usedPercent = 100 - round(($free / $total) * 100);

        if ($usedPercent >= 95) {
            return [
                'FAIL',
                "Disk {$usedPercent}% full ({$this->formatBytes($free)} free of {$this->formatBytes(
                    $total,
                )})",
            ];
        }

        if ($usedPercent >= 85) {
            return [
                'WARN',
                "Disk {$usedPercent}% full ({$this->formatBytes($free)} free of {$this->formatBytes(
                    $total,
                )})",
            ];
        }

        return ['OK', "{$usedPercent}% used — {$this->formatBytes($free)} free"];
    }

    protected function checkQueue(): array
    {
        try {
            $pending = DB::table('jobs')->count();
            $failed = DB::table('failed_jobs')->count();

            $detail = "{$pending} pending, {$failed} failed";

            if ($failed > 100) {
                return ['WARN', $detail.' — consider running queue:prune-failed'];
            }

            return ['OK', $detail];
        } catch (\Throwable $e) {
            return ['WARN', 'Queue tables not found or inaccessible'];
        }
    }

    protected function checkCache(): array
    {
        try {
            Cache::store()->put(CacheKeys::HEALTH_CHECK, true, 10);
            $val = Cache::store()->get(CacheKeys::HEALTH_CHECK);

            return [$val ? 'OK' : 'FAIL', 'Cache driver responding'];
        } catch (\Throwable $e) {
            return ['FAIL', 'Cache error: '.$e->getMessage()];
        }
    }

    protected function checkAppKey(): array
    {
        $key = config('app.key');

        if ($key === null || $key === '') {
            return ['FAIL', 'Application key is missing'];
        }

        if (! str_starts_with($key, 'base64:')) {
            return ['WARN', 'Application key format is unusual (expected base64:)'];
        }

        return ['OK', 'Application key is set and valid'];
    }

    protected function checkStorageLink(): array
    {
        $link = public_path('storage');
        $exists = is_link($link);

        return [
            $exists ? 'OK' : 'FAIL',
            $exists ? 'public/storage link exists' : 'public/storage link is missing',
        ];
    }

    protected function checkMaintenanceMode(): array
    {
        if (app()->isDownForMaintenance()) {
            return ['WARN', 'Application is in maintenance mode'];
        }

        return ['OK', 'Application is live'];
    }

    private function formatBytes(float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    private function memoryInBytes(string $value): int
    {
        $value = trim($value);
        $unit = strtolower(substr($value, -1));
        $num = (int) substr($value, 0, -1);

        return match ($unit) {
            'g' => $num * 1024 * 1024 * 1024,
            'm' => $num * 1024 * 1024,
            'k' => $num * 1024,
            default => (int) $value,
        };
    }
}
