<?php

declare(strict_types=1);

namespace App\Modules\SysAdmin\Domain\Announcement\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\SysAdmin\Domain\Announcement\Models\Announcement;

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
