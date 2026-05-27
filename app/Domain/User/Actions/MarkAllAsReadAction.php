<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Support\CacheKeys;
use App\Domain\User\Models\Notification;
use Illuminate\Support\Facades\Cache;

/**
 * Stateless Action to mark all user's notifications as read.
 *
 * S1 - Secure: Only user can mark their own notifications.
 * S2 - Sustain: Batch operation.
 */
class MarkAllAsReadAction extends BaseAction
{
    public function execute(string $userId): int
    {
        $updated = Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        Cache::forget(CacheKeys::NOTIFICATION_UNREAD.$userId);

        return $updated;
    }
}
