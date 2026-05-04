<?php

declare(strict_types=1);

namespace App\Console\Commands\Core;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * System Cleanup Command.
 *
 * S2 - Sustain: Automates routine maintenance.
 * S3 - Scalable: Keeps database and logs manageable.
 */
class CleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:cleanup {--force : Do not ask for confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform routine system maintenance and cleanup tasks';

    /**
     * Execute the console command.
     */
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
            $this->task($description, function () use ($command) {
                try {
                    Artisan::call($command);

                    return true;
                } catch (\Exception $e) {
                    return false;
                }
            });
        }

        $this->info("\nSystem maintenance completed.");

        return Command::SUCCESS;
    }
}
