<?php

declare(strict_types=1);

namespace App\Domain\Admin\Actions;

use App\Domain\Admin\Models\Notification;
use App\Domain\Core\Actions\BaseAction;

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
        return Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }
}
