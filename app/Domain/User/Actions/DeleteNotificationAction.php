<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\User\Models\Notification;

/**
 * Stateless Action to delete a notification.
 *
 * Ownership verification is the caller's responsibility.
 * S2 - Sustain: Clean removal.
 */
class DeleteNotificationAction extends BaseAction
{
    public function execute(Notification $notification): void
    {
        $notification->delete();
    }
}
