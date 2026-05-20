<?php

declare(strict_types=1);

namespace App\Domain\Admin\Actions;

use App\Domain\Admin\Models\Notification;
use App\Domain\Core\Actions\BaseAction;

/**
 * Stateless Action to delete a notification.
 *
 * S1 - Secure: Only owner can delete.
 * S2 - Sustain: Clean removal.
 */
class DeleteNotificationAction extends BaseAction
{
    public function execute(Notification $notification): void
    {
        $notification->delete();
    }
}
