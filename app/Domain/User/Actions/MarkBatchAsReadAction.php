<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\User\Models\Notification;

class MarkBatchAsReadAction extends BaseAction
{
    public function execute(string $userId, array $ids): int
    {
        return Notification::whereIn('id', $ids)
            ->where('user_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }
}
