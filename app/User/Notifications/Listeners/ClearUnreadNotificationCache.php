<?php

declare(strict_types=1);

namespace App\User\Notifications\Listeners;

use App\User\Notifications\Events\NotificationRead;
use App\User\Notifications\Events\NotificationSent;
use App\User\Profile\Events\ProfileUpdated;
use Illuminate\Support\Facades\Cache;

final class ClearUnreadNotificationCache
{
    public function handle(NotificationSent|NotificationRead|ProfileUpdated $event): void
    {
        $userId = match (true) {
            $event instanceof ProfileUpdated => $event->profile->user_id,
            default => $event->notification->user_id,
        };

        Cache::forget(
            config('cache-keys.notification_unread').$userId,
        );
    }
}
