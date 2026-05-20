<?php

declare(strict_types=1);

namespace App\Domain\Core\Console\Commands;

use App\Domain\Core\Support\SmartLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class CleanupCommand extends Command
{
    protected $signature = 'system:cleanup
        {--force : Do not ask for confirmation}
        {--log-retention=30 : Days to retain log files}';

    protected $description = 'Perform routine system maintenance and cleanup tasks';

    public function handle(): int
    {
        if (
            ! $this->option('force') &&
            ! $this->confirm('This will perform system cleanup tasks. Continue?')
        ) {
            return Command::SUCCESS;
        }

        $this->info('Starting System Cleanup...');

        $tasks = [
            'auth:clear-resets' => 'Clearing expired password resets',
            'cache:prune-stale-tags' => 'Pruning stale cache tags',
            'queue:prune-failed' => 'Pruning stale failed jobs',
            'activitylog:clean' => 'Cleaning old activity logs',
            'media-library:clean' => 'Cleaning old media files',
        ];

        foreach ($tasks as $command => $description) {
            $this->info("  → {$description}...");

            try {
                Artisan::call($command);
            } catch (\Throwable $e) {
                SmartLogger::warning("Cleanup task failed: {$command}")
                    ->withPayload(['error' => $e->getMessage()])
                    ->systemOnly()
                    ->save();

                $this->warn("    ✗ Failed: {$e->getMessage()}");
            }
        }

        $this->pruneLogFiles();

        $this->info("\nSystem maintenance completed.");

        return Command::SUCCESS;
    }

    protected function pruneLogFiles(): void
    {
        $retention = (int) $this->option('log-retention');
        $logDir = storage_path('logs');
        $cutoff = now()->subDays($retention);
        $count = 0;

        $files = File::glob($logDir.'/laravel-*.log');

        foreach ($files as $file) {
            $mtime = File::lastModified($file);

            if ($mtime < $cutoff->timestamp) {
                File::delete($file);
                $count++;
            }
        }

        if ($count > 0) {
            $this->info("  → Pruned {$count} log file(s) older than {$retention} days");
        } else {
            $this->info('  → No log files to prune');
        }
    }
}
