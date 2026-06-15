<?php

declare(strict_types=1);

namespace App\SysAdmin\Announcement\Actions;

use App\Core\Actions\BaseCommandAction;
use App\SysAdmin\Announcement\Models\Announcement;

final class DeleteAnnouncementAction extends BaseCommandAction
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
