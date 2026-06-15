<?php

declare(strict_types=1);

namespace App\User\Notifications\Actions;

use App\Core\Actions\BaseCommandAction;
use App\User\Notifications\Events\NotificationRead;
use App\User\Notifications\Models\Notification;
use Illuminate\Support\Facades\Event;

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
        if (! $notification->is_read) {
            $notification->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        Event::dispatch(new NotificationRead($notification));

        return $notification->fresh();
    }
}
