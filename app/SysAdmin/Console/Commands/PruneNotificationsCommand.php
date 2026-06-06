<?php

declare(strict_types=1);

namespace App\SysAdmin\Console\Commands;

use App\User\Notifications\Models\Notification;
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
            $this->components->error(__('sysadmin.prune_notifications.invalid_days'));

            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);

        $deleted = Notification::where('is_read', true)
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->components->info(__('sysadmin.prune_notifications.completed', ['count' => $deleted, 'days' => $days]));

        return self::SUCCESS;
    }
}
