<?php

declare(strict_types=1);

namespace App\Domain\Admin\Console\Commands;

use App\Domain\Admin\Actions\SendAnnouncementAction;
use App\Domain\Admin\Enums\AnnouncementStatus;
use App\Domain\Admin\Models\Announcement;
use Illuminate\Console\Command;

class PublishScheduledAnnouncementsCommand extends Command
{
    protected $signature = 'announcements:publish';

    protected $description = 'Publish all scheduled announcements whose scheduled_at has passed';

    public function handle(SendAnnouncementAction $action): int
    {
        $due = Announcement::where('status', AnnouncementStatus::SCHEDULED)
            ->where('scheduled_at', '<=', now())
            ->get();

        if ($due->isEmpty()) {
            $this->info('No scheduled announcements due for publication.');

            return self::SUCCESS;
        }

        foreach ($due as $announcement) {
            $action->publish($announcement);
            $this->line("Published: {$announcement->title}");
        }

        $this->info("Published {$due->count()} scheduled announcement(s).");

        return self::SUCCESS;
    }
}
