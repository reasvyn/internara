<?php

declare(strict_types=1);

namespace App\SysAdmin\Announcement\Actions;

use App\Core\Actions\BaseAction;
use App\SysAdmin\Announcement\Models\Announcement;

final class DeleteAnnouncementAction extends BaseAction
{
    public function execute(Announcement $announcement): void
    {
        $this->transaction(function () use ($announcement) {
            $announcement->delete();

            $this->log('announcement_deleted', $announcement, [
                'title' => $announcement->title,
            ]);
        });
    }
}
