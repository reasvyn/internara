<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\Password\Listeners;

use App\Modules\Auth\Domain\Password\Events\PasswordUpdated;
use App\Modules\Core\Channels\Data\NotificationData;
use App\Modules\Core\Contracts\SendsNotifications;
use Illuminate\Contracts\Queue\ShouldQueue;

final class InvalidateSessionOnPasswordChange implements ShouldQueue
{
    public function __construct(protected SendsNotifications $sendNotification) {}

    public function handle(PasswordUpdated $event): void
    {
        $user = $event->user;

        $this->sendNotification->execute(new NotificationData(
            userId: $user->id,
            type: 'password_changed',
            title: __('notifications.password_changed.title'),
            message: __('notifications.password_changed.message'),
            link: route('profile'),
        ));
    }
}
