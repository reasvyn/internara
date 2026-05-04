<?php

declare(strict_types=1);

namespace App\Domain\Notification\Channels;

use App\Domain\Notification\Actions\SendNotificationAction;
use Illuminate\Notifications\Notification;

/**
 * Custom Database Channel for DDD-based notifications.
 *
 * S2 - Sustain: Leverages SendNotificationAction for consistency.
 * S3 - Scalable: Decouples notification storage from Laravel's default table.
 */
class CustomDatabaseChannel
{
    public function __construct(
        protected SendNotificationAction $sendNotification
    ) {}

    /**
     * Send the given notification.
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toCustomDatabase')) {
            return;
        }

        $data = $notification->toCustomDatabase($notifiable);

        $this->sendNotification->execute(
            userId: (string) $notifiable->id,
            type: $data['type'] ?? 'general',
            title: $data['title'] ?? 'Notification',
            message: $data['message'] ?? null,
            data: $data['data'] ?? null,
            link: $data['link'] ?? null
        );
    }
}
