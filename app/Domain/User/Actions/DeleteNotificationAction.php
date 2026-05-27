<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Support\CacheKeys;
use App\Domain\User\Models\Notification;
use Illuminate\Support\Facades\Cache;

/**
 * Stateless Action to delete a notification.
 *
 * Ownership verification is the caller's responsibility.
 * S2 - Sustain: Clean removal.
 */
class DeleteNotificationAction extends BaseAction
{
    public function execute(Notification $notification): void
    {
        $userId = $notification->user_id;
        $notification->delete();

        Cache::forget(CacheKeys::NOTIFICATION_UNREAD.$userId);
    }
}
