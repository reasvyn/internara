<?php

declare(strict_types=1);

namespace App\Auth\Password\Listeners;

use App\Auth\Password\Events\PasswordUpdated;
use App\Core\Contracts\SendsNotifications;
use Illuminate\Contracts\Queue\ShouldQueue;

final class InvalidateSessionOnPasswordChange implements ShouldQueue
{
    public function __construct(
        protected SendsNotifications $sendNotification,
    ) {}

    public function handle(PasswordUpdated $event): void
    {
        $user = $event->user;

        $this->sendNotification->execute(
            userId: $user->id,
            type: 'password_changed',
            title: __('notifications.password_changed.title'),
            message: __('notifications.password_changed.message'),
            link: route('user.profile'),
        );
    }
}
