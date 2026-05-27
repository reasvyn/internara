<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Support\CacheKeys;
use App\Domain\User\Models\Notification;
use Illuminate\Support\Facades\Cache;

/**
 * Stateless Action to mark notification as read.
 *
 * S1 - Secure: Only notification owner can mark as read.
 * S2 - Sustain: Single-purpose action.
 */
class MarkAsReadAction extends BaseAction
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
