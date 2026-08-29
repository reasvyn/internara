<?php

declare(strict_types=1);

namespace App\Modules\SysAdmin\Domain\Announcement\Console\Commands;

use App\Modules\SysAdmin\Domain\Announcement\Actions\PublishAnnouncementAction;
use App\Modules\SysAdmin\Domain\Announcement\Enums\AnnouncementStatus;
use App\Modules\SysAdmin\Domain\Announcement\Models\Announcement;
use Illuminate\Console\Command;

class PublishScheduledAnnouncementsCommand extends Command
{
    protected $signature = 'announcements:publish';

    protected $description = 'Publish all scheduled announcements whose scheduled_at has passed';

    public function handle(PublishAnnouncementAction $action): int
    {
        $due = Announcement::where('status', AnnouncementStatus::SCHEDULED)
            ->where('scheduled_at', '<=', now())
            ->get();

        if ($due->isEmpty()) {
            $this->components->info(__('sysadmin.publish_announcements.none_found'));

            return self::SUCCESS;
        }

        foreach ($due as $announcement) {
            $action->execute($announcement);
            $this->components->task(
                __('sysadmin.publish_announcements.published', ['title' => $announcement->title]),
                fn () => true,
            );
        }

        $this->newLine();
        $this->components->info(
            __('sysadmin.publish_announcements.completed', ['count' => $due->count()]),
        );

        return self::SUCCESS;
    }
}
