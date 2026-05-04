<?php

declare(strict_types=1);

namespace App\Domain\Notification\Actions;

use App\Domain\Notification\Models\Notification;

/**
 * Stateless Action to delete a notification.
 *
 * S1 - Secure: Only owner can delete.
 * S2 - Sustain: Clean removal.
 */
class DeleteNotificationAction
{
    public function execute(Notification $notification): void
    {
        $notification->delete();
    }
}
