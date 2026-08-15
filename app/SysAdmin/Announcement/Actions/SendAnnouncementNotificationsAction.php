<?php

declare(strict_types=1);

namespace App\SysAdmin\Announcement\Actions;

use App\Core\Actions\BaseProcessAction;
use App\SysAdmin\Announcement\Models\Announcement;
use App\SysAdmin\Announcement\Notifications\AnnouncementNotification;
use App\User\Models\User;
use Illuminate\Support\Facades\Notification;

final class SendAnnouncementNotificationsAction extends BaseProcessAction
{
    /**
     * @param array<string, mixed> $config
     */
    public function execute(Announcement $announcement, array $config): void
    {
        $users = User::query();

        if (! empty($config['target_roles'])) {
            $senderRoles = auth()->user()?->roles->pluck('name')->toArray() ?? [];

            $users
                ->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', $senderRoles))
                ->whereHas('roles', fn ($q) => $q->whereIn('name', $config['target_roles']));
        }

        Notification::send(
            $users->get(),
            new AnnouncementNotification(
                title: $announcement->title,
                message: $announcement->message,
                link: $announcement->link,
            ),
        );
    }
}
