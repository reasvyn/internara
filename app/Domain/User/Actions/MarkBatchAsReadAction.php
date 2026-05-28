<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Support\CacheKeys;
use App\Domain\User\Models\Notification;
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
