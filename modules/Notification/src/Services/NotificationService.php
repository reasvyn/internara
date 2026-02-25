<?php

declare(strict_types=1);

namespace Modules\Notification\Services;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Modules\Notification\Services\Contracts\NotificationService as Contract;
use Modules\Shared\Services\BaseService;

class NotificationService extends BaseService implements Contract
{
    /**
     * {@inheritdoc}
     */
    public function send(mixed $recipients, Notification $notification): void
    {
        NotificationFacade::send($recipients, $notification);
    }

    /**
     * {@inheritdoc}
     */
    public function sendNow(mixed $recipients, Notification $notification): void
    {
        NotificationFacade::sendNow($recipients, $notification);
    }
}
