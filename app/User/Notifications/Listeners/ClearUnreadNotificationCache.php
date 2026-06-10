<?php

declare(strict_types=1);

namespace App\User\Notifications\Listeners;

use App\User\Notifications\Events\NotificationRead;
use App\User\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Cache;

final class ClearUnreadNotificationCache
{
    public function handle(NotificationSent|NotificationRead $event): void
    {
        Cache::forget(
            config('cache-keys.notification_unread').$event->notification->user_id,
        );
    }
}
