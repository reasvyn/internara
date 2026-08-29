<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Notifications\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\User\Domain\Notifications\Events\NotificationRead;
use App\Modules\User\Domain\Notifications\Models\Notification;

/**
 * Stateless Action to mark notification as read.
 *
 * S1 - Secure: Only notification owner can mark as read.
 * S2 - Sustain: Single-purpose action.
 */
final class MarkAsReadAction extends BaseCommandAction
{
    public function execute(Notification $notification): Notification
    {
        return $this->transaction(function () use ($notification) {
            if (! $notification->is_read) {
                $notification->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);
            }

            event(new NotificationRead($notification));

            $this->log('notification_marked_read', $notification, [
                'notification_id' => $notification->id,
                'user_id' => $notification->user_id,
            ]);

            return $notification->fresh();
        });
    }
}
