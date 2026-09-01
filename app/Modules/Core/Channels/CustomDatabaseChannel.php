<?php

declare(strict_types=1);

namespace App\Modules\Core\Channels;

use App\Modules\Core\Channels\Data\NotificationData;
use App\Modules\Core\Contracts\SendsNotifications;
use App\Modules\Core\Services\SmartLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;

class CustomDatabaseChannel
{
    private const DEFAULT_TYPE = 'general';
    private const DEFAULT_TITLE = 'Notification';

    public function __construct(protected readonly SendsNotifications $sendNotification) {}

    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toCustomDatabase')) {
            return;
        }

        $userId = $notifiable instanceof Model ? $notifiable->getKey() : ($notifiable->id ?? null);

        if ($userId === null || $userId === '') {
            return;
        }

        $data = $notification->toCustomDatabase($notifiable);

        if (! isset($data['type'])) {
            SmartLogger::warning('Notification missing type key')
                ->withPayload(['notification_class' => get_class($notification)])
                ->withPiiMasking()
                ->systemOnly()
                ->save();
        }

        if (! isset($data['title'])) {
            SmartLogger::warning('Notification missing title key')
                ->withPayload(['notification_class' => get_class($notification)])
                ->withPiiMasking()
                ->systemOnly()
                ->save();
        }

        $this->sendNotification->execute(new NotificationData(
            userId: (string) $userId,
            type: $data['type'] ?? self::DEFAULT_TYPE,
            title: $data['title'] ?? self::DEFAULT_TITLE,
            message: $data['message'] ?? null,
            data: $data['data'] ?? null,
            link: $data['link'] ?? null,
        ));
    }
}
