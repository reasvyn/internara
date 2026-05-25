<?php

declare(strict_types=1);

namespace App\Domain\Admin\Console\Commands;

use App\Domain\User\Models\Notification;
use Illuminate\Console\Command;

class PruneNotificationsCommand extends Command
{
    protected $signature = 'notifications:prune
        {--days=30 : Delete read notifications older than this many days}';

    protected $description = 'Delete read notifications older than the specified retention period';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        if ($days < 1) {
            $this->error('Retention days must be at least 1.');

            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);

        $deleted = Notification::where('is_read', true)
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("Pruned {$deleted} read notification(s) older than {$days} days.");

        return self::SUCCESS;
    }
}
