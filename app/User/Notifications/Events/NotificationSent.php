<?php

declare(strict_types=1);

namespace App\User\Notifications\Events;

use App\Core\Events\BaseEvent;
use App\User\Notifications\Models\Notification;

final class NotificationSent extends BaseEvent
{
    public function __construct(public Notification $notification) {}

    public function eventName(): string
    {
        return 'notification.sent';
    }
}
