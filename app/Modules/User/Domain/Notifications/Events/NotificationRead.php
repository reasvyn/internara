<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Notifications\Events;

use App\Modules\Core\Events\BaseEvent;
use App\Modules\User\Domain\Notifications\Models\Notification;

final class NotificationRead extends BaseEvent
{
    public function __construct(public Notification $notification) {}

    public function eventName(): string
    {
        return 'notification.read';
    }
}
