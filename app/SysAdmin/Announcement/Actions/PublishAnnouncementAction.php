<?php

declare(strict_types=1);

namespace App\SysAdmin\Announcement\Actions;

use App\Core\Actions\BaseCommandAction;
use App\SysAdmin\Announcement\Enums\AnnouncementStatus;
use App\SysAdmin\Announcement\Models\Announcement;
use App\SysAdmin\Announcement\Notifications\AnnouncementNotification;
use App\User\Models\User;
use Illuminate\Support\Facades\Notification;

final class PublishAnnouncementAction extends BaseCommandAction
{
    public function execute(Announcement $announcement): void
    {
        $this->transaction(function () use ($announcement) {
            $announcement->update([
                'status' => AnnouncementStatus::PUBLISHED,
                'scheduled_at' => null,
            ]);

            $users = User::query();

            if (! empty($announcement->target_roles)) {
                $users
                    ->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', auth()->user()->roles->pluck('name')))
                    ->whereHas('roles', fn ($q) => $q->whereIn('name', $announcement->target_roles));
            }

            Notification::send(
                $users->get(),
                new AnnouncementNotification(
                    title: $announcement->title,
                    message: $announcement->message,
                    link: $announcement->link,
                ),
            );

            $this->log('announcement_published', $announcement, [
                'title' => $announcement->title,
            ]);
        });
    }
}
