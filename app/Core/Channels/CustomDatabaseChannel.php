<?php

declare(strict_types=1);

namespace App\Core\Channels;

use App\Core\Contracts\SendsNotifications;
use App\Core\Support\SmartLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;

/**
 * Custom Database Channel for application notifications.
 *
 * S2 - Sustain: Leverages SendNotificationAction for consistency.
 * S3 - Scalable: Decouples notification storage from Laravel's default table.
 */
class CustomDatabaseChannel
{
    public function __construct(
        protected SendsNotifications $sendNotification
    ) {}

    /**
     * Send the given notification.
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toCustomDatabase')) {
            return;
        }

        $userId = $notifiable instanceof Model
            ? $notifiable->getKey()
            : ($notifiable->id ?? null);

        if ($userId === null || $userId === '') {
            return;
        }

        $data = $notification->toCustomDatabase($notifiable);

        $type = $data['type'] ?? 'general';
        $title = $data['title'] ?? 'Notification';

        if (! isset($data['type'])) {
            SmartLogger::warning('Notification missing type key')
                ->withPayload(['notification_class' => get_class($notification)])
                ->systemOnly()
                ->save();
        }

        if (! isset($data['title'])) {
            SmartLogger::warning('Notification missing title key')
                ->withPayload(['notification_class' => get_class($notification)])
                ->systemOnly()
                ->save();
        }

        $this->sendNotification->execute(
            userId: (string) $userId,
            type: $type,
            title: $title,
            message: $data['message'] ?? null,
            data: $data['data'] ?? null,
            link: $data['link'] ?? null
        );
    }
}
