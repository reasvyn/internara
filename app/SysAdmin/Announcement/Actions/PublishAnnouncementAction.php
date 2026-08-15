<?php

declare(strict_types=1);

namespace App\SysAdmin\Announcement\Actions;

use App\Core\Actions\BaseCommandAction;
use App\SysAdmin\Announcement\Enums\AnnouncementStatus;
use App\SysAdmin\Announcement\Models\Announcement;

final class PublishAnnouncementAction extends BaseCommandAction
{
    public function __construct(
        private readonly SendAnnouncementNotificationsAction $sendNotifications,
    ) {}

    public function execute(Announcement $announcement): void
    {
        $this->transaction(function () use ($announcement) {
            $announcement->update([
                'status' => AnnouncementStatus::PUBLISHED,
                'scheduled_at' => null,
            ]);

            $this->sendNotifications->execute($announcement, [
                'target_roles' => $announcement->target_roles,
            ]);

            $this->log('announcement_published', $announcement, [
                'title' => $announcement->title,
            ]);
        });
    }
}
