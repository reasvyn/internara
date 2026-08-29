<?php

declare(strict_types=1);

namespace App\Modules\SysAdmin\Domain\Announcement\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\SysAdmin\Domain\Announcement\Enums\AnnouncementStatus;
use App\Modules\SysAdmin\Domain\Announcement\Models\Announcement;

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
