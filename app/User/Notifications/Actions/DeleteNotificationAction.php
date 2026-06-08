<?php

declare(strict_types=1);

namespace App\User\Notifications\Actions;

use App\Core\Actions\BaseAction;
use App\User\Notifications\Models\Notification;
use Illuminate\Support\Facades\Cache;

/**
 * Stateless Action to delete a notification.
 *
 * Ownership verification is the caller's responsibility.
 * S2 - Sustain: Clean removal.
 */
final class DeleteNotificationAction extends BaseAction
{
    public function execute(Notification $notification): void
    {
        $userId = $notification->user_id;
        $notification->delete();

        Cache::forget(config('cache-keys.notification_unread').$userId);
    }
}
