<?php

declare(strict_types=1);

namespace App\User\Notifications\Actions;

use App\Core\Actions\BaseCommandAction;
use App\User\Notifications\Models\Notification;
use Illuminate\Support\Facades\Cache;

final class MarkBatchAsReadAction extends BaseCommandAction
{
    public function execute(string $userId, array $ids): int
    {
        $updated = Notification::whereIn('id', $ids)
            ->where('user_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        Cache::forget(config('cache-keys.notification_unread').$userId);

        return $updated;
    }
}
