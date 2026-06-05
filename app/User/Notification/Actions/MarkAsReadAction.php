<?php

declare(strict_types=1);

namespace App\User\Notification\Actions;

use App\Core\Actions\BaseAction;
use App\Support\CacheKeys;
use App\User\Notification\Models\Notification;
use Illuminate\Support\Facades\Cache;

/**
 * Stateless Action to mark notification as read.
 *
 * S1 - Secure: Only notification owner can mark as read.
 * S2 - Sustain: Single-purpose action.
 */
final class MarkAsReadAction extends BaseAction
{
    public function execute(Notification $notification): Notification
    {
        if (! $notification->is_read) {
            $notification->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        Cache::forget(CacheKeys::NOTIFICATION_UNREAD.$notification->user_id);

        return $notification->fresh();
    }
}
