<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Notify\Events;

use App\Modules\Core\Events\BaseEvent;
use App\Modules\User\Domain\Notify\Models\Notification;

final class NotificationSent extends BaseEvent
{
    public function __construct(public Notification $notification) {}

    public function eventName(): string
    {
        return 'notification.sent';
    }
}
