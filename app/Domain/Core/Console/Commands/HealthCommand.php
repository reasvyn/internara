<?php

declare(strict_types=1);

namespace App\Domain\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class HealthCommand extends Command
{
    protected $signature = 'system:health
        {--json : Output results as JSON}';

    protected $description = 'Perform a comprehensive system health check';

    public function handle(): int
    {
        $results = [
            ['Environment', ...$this->checkEnvironment()],
            ['PHP Version', ...$this->checkPhpVersion()],
            ['PHP Extensions', ...$this->checkExtensions()],
            ['PHP Memory', ...$this->checkMemory()],
            ['Database', ...$this->checkDatabase()],
            ['Storage', ...$this->checkStorage()],
            ['Disk Space', ...$this->checkDiskSpace()],
            ['Queue', ...$this->checkQueue()],
            ['Cache', ...$this->checkCache()],
            ['App Key', ...$this->checkAppKey()],
            ['Storage Link', ...$this->checkStorageLink()],
            ['Maintenance Mode', ...$this->checkMaintenanceMode()],
        ];

        if ($this->option('json')) {
            $this->line(json_encode($results, JSON_PRETTY_PRINT));

            return Command::SUCCESS;
        }

        $this->header();
        $this->table(['Service', 'Status', 'Details'], $results);

        $hasFailures = collect($results)->contains(fn ($r) => $r[1] === 'FAIL');

        if ($hasFailures) {
            $this->error("\nSystem health checks failed! Please review the details above.");

            return Command::FAILURE;
        }

        if ($this->option('verbose')) {
            $this->line('');
            $this->line('PHP Extensions loaded: '.implode(', ', get_loaded_extensions()));
        }

        $this->info("\nAll system health checks passed successfully.");

        return Command::SUCCESS;
    }

    protected function header(): void
    {
        $this->line('<fg=cyan>==================================================</>');
        $this->line('<fg=cyan>         INTERNARA SYSTEM HEALTH CHECK            </>');
        $this->line('<fg=cyan>==================================================</>');
        $this->line('Time: '.now()->toDateTimeString());
        $this->line('Env:  '.app()->environment());
        $this->line('');
    }

    protected function checkEnvironment(): array
    {
        $exists = File::exists(base_path('.env'));

        return [$exists ? 'OK' : 'FAIL', $exists ? '.env file detected' : '.env file is missing!'];
    }

    protected function checkPhpVersion(): array
    {
        $required = '8.4.0';
        $current = PHP_VERSION;

        if (version_compare($current, $required, '>=')) {
            return ['OK', "PHP {$current} (required: {$required}+)"];
        }

        return ['FAIL', "PHP {$current} is below required {$required}"];
    }

    protected function checkExtensions(): array
    {
        $required = config('setup.requirements.extensions', [
            'bcmath', 'ctype', 'fileinfo', 'mbstring', 'openssl',
            'pdo', 'tokenizer', 'xml', 'curl', 'gd', 'intl', 'zip',
        ]);

        $missing = array_filter($required, fn ($ext) => ! extension_loaded($ext));

        if ($missing === []) {
            return ['OK', 'All '.count($required).' required extensions loaded'];
        }

        return ['FAIL', 'Missing: '.implode(', ', $missing)];
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
            return ['FAIL', "Disk {$usedPercent}% full ({$this->formatBytes($free)} free of {$this->formatBytes($total)})"];
        }

        if ($usedPercent >= 85) {
            return ['WARN', "Disk {$usedPercent}% full ({$this->formatBytes($free)} free of {$this->formatBytes($total)})"];
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
            Cache::store()->put('health_check', true, 10);
            $val = Cache::store()->get('health_check');

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

        return [$exists ? 'OK' : 'FAIL', $exists ? 'public/storage link exists' : 'public/storage link is missing'];
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
