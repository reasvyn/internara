<?php

declare(strict_types=1);

namespace App\SysAdmin\Console\Commands;

use App\Core\Support\SmartLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class SystemCleanupCommand extends Command
{
    protected $signature = 'system:cleanup
        {--force : Do not ask for confirmation}
        {--log-retention=30 : Days to retain log files}';

    protected $description = 'Perform routine system maintenance and cleanup tasks';

    public function handle(): int
    {
        try {
            if (
                ! $this->option('force') &&
                ! $this->components->confirm(__('setup.system.cleanup_confirm'), default: true)
            ) {
                return Command::SUCCESS;
            }

            $this->info(__('setup.system.cleanup_starting'));

            $tasks = [
                'auth:clear-resets' => __('setup.system.cleanup_task_resets'),
                'cache:prune-stale-tags' => __('setup.system.cleanup_task_cache_tags'),
                'queue:prune-failed' => __('setup.system.cleanup_task_failed_jobs'),
                'activitylog:clean' => __('setup.system.cleanup_task_activity_log'),
                'media-library:clean' => __('setup.system.cleanup_task_media'),
            ];

            foreach ($tasks as $command => $description) {
                try {
                    Artisan::call($command);
                    $this->components->task($description, fn () => true);
                } catch (\Throwable $e) {
                    SmartLogger::warning("Cleanup task failed: {$command}")
                        ->withPayload(['error' => $e->getMessage()])
                        ->systemOnly()
                        ->save();

                    $this->components->task($description, fn () => false);
                }
            }

            $this->pruneLogFiles();

            $this->newLine();
            $this->components->info(__('setup.system.cleanup_completed'));

            SmartLogger::info(__('setup.system.cleanup_completed'))
                ->module('system')
                ->event('cleanup.completed')
                ->save();

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            SmartLogger::error('System cleanup failed')
                ->module('system')
                ->event('cleanup.failed')
                ->withPayload(['error' => $e->getMessage()])
                ->save();

            $this->error(__('setup.system.cleanup_completed').': '.$e->getMessage());

            return Command::FAILURE;
        }
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
            $this->components->task(
                __('setup.system.cleanup_pruned_logs', ['count' => $count, 'days' => $retention]),
                fn () => true,
            );
        } else {
            $this->components->task(
                __('setup.system.cleanup_no_logs'),
                fn () => true,
            );
        }
    }
}
