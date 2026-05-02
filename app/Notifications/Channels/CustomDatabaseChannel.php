<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Models\Notification as NotificationModel;
use Illuminate\Notifications\Notification;

class CustomDatabaseChannel
{
    /**
     * Send the given notification.
     *
     * @param mixed $notifiable
     *
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        $data = $notification->toCustomDatabase($notifiable);

        NotificationModel::create([
            'user_id' => $notifiable->id,
            'type' => $data['type'] ?? get_class($notification),
            'title' => $data['title'],
            'message' => $data['message'] ?? null,
            'data' => $data['data'] ?? [],
            'link' => $data['link'] ?? null,
            'is_read' => false,
        ]);
    }
}
