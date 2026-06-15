<?php

declare(strict_types=1);

namespace App\User\Notifications\Actions;

use App\Core\Actions\BaseCommandAction;
use App\User\Notifications\Models\Notification;
use Illuminate\Support\Facades\Cache;

/**
 * Stateless Action to mark all user's notifications as read.
 *
 * S1 - Secure: Only user can mark their own notifications.
 * S2 - Sustain: Batch operation.
 */
final class MarkAllAsReadAction extends BaseCommandAction
{
    public function execute(string $userId): int
    {
        $updated = Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        Cache::forget(config('cache-keys.notification_unread').$userId);

        return $updated;
    }
}
