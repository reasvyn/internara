<?php

declare(strict_types=1);

namespace App\Console\Commands\System;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * System Health Check Command.
 *
 * S1 - Secure: Minimal exposure of sensitive info.
 * S2 - Sustain: Proactive monitoring tool for maintainers.
 */
class HealthCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:health';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform a comprehensive system health check';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->header();

        $results = [
            ['Environment', ...$this->checkEnvironment()],
            ['Database', ...$this->checkDatabase()],
            ['Storage', ...$this->checkStorage()],
            ['Queue', ...$this->checkQueue()],
            ['Caching', ...$this->checkCache()],
        ];

        $this->table(['Service', 'Status', 'Details'], $results);

        $hasFailures = collect($results)->contains(fn ($r) => $r[1] === 'FAIL');

        if ($hasFailures) {
            $this->error("\nSystem health checks failed! Please review the details above.");

            return Command::FAILURE;
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

        return [
            $exists ? 'OK' : 'FAIL',
            $exists ? '.env file detected' : '.env file is missing!',
        ];
    }

    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            $tables = count(DB::select('SELECT name FROM sqlite_master WHERE type="table"'));

            return ['OK', "Connected ({$tables} tables)"];
        } catch (\Exception $e) {
            return ['FAIL', 'Connection failed: '.$exception->getMessage()];
        }
    }

    protected function checkStorage(): array
    {
        $writable = File::isWritable(storage_path('framework/views')) &&
                    File::isWritable(storage_path('logs'));

        return [
            $writable ? 'OK' : 'FAIL',
            $writable ? 'Storage directories are writable' : 'Storage permissions issue detected',
        ];
    }

    protected function checkQueue(): array
    {
        try {
            $pending = DB::table('jobs')->count();
            $failed = DB::table('failed_jobs')->count();

            return ['OK', "{$pending} pending, {$failed} failed jobs"];
        } catch (\Exception $e) {
            return ['WARN', 'Queue tables not found or inaccessible'];
        }
    }

    protected function checkCache(): array
    {
        try {
            cache()->put('health_check', true, 10);
            $val = cache()->get('health_check');

            return [$val ? 'OK' : 'FAIL', 'Cache driver responding'];
        } catch (\Exception $e) {
            return ['FAIL', 'Cache error: '.$e->getMessage()];
        }
    }
}
