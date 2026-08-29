<?php

declare(strict_types=1);

namespace App\Modules\SysAdmin\Domain\Announcement\Actions;

use App\Modules\Core\Actions\BaseProcessAction;
use App\Modules\SysAdmin\Domain\Announcement\Models\Announcement;
use App\Modules\SysAdmin\Domain\Announcement\Notifications\AnnouncementNotification;
use App\Modules\User\Models\User;
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
