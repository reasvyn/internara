<?php

declare(strict_types=1);

namespace App\User\Notifications\Events;

use App\User\Notifications\Models\Notification;

final readonly class NotificationRead
{
    public function __construct(
        public Notification $notification,
    ) {}
}