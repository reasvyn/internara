<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Notify\Listeners;

use App\Modules\User\Domain\Notify\Events\NotificationRead;
use App\Modules\User\Domain\Notify\Events\NotificationSent;
use App\Modules\User\Domain\Profile\Events\ProfileUpdated;
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
