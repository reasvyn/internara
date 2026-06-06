<?php

declare(strict_types=1);

namespace App\User\Notifications\Actions;

use App\Core\Actions\BaseAction;
use App\Support\CacheKeys;
use App\User\Notifications\Models\Notification;
use Illuminate\Support\Facades\Cache;

final class MarkBatchAsReadAction extends BaseAction
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

        Cache::forget(CacheKeys::NOTIFICATION_UNREAD.$userId);

        return $updated;
    }
}
