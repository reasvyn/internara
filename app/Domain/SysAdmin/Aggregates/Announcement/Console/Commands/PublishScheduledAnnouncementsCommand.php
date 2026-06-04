<?php

declare(strict_types=1);

namespace App\Domain\SysAdmin\Aggregates\Announcement\Console\Commands;

use App\Domain\SysAdmin\Aggregates\Announcement\Actions\SendAnnouncementAction;
use App\Domain\SysAdmin\Aggregates\Announcement\Enums\AnnouncementStatus;
use App\Domain\SysAdmin\Aggregates\Announcement\Models\Announcement;
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
            $this->components->info(__('sysadmin.publish_announcements.none_found'));

            return self::SUCCESS;
        }

        foreach ($due as $announcement) {
            $action->publish($announcement);
            $this->components->task(__('sysadmin.publish_announcements.published', ['title' => $announcement->title]), fn () => true);
        }

        $this->newLine();
        $this->components->info(__('sysadmin.publish_announcements.completed', ['count' => $due->count()]));

        return self::SUCCESS;
    }
}
