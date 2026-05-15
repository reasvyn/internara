<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\CustomDatabaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AnnouncementNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public ?string $link = null,
    ) {}

    public function via($notifiable): array
    {
        return ['broadcast', CustomDatabaseChannel::class];
    }

    public function toBroadcast($notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'link' => $this->link,
        ];
    }

    public function toCustomDatabase($notifiable): array
    {
        return [
            'type' => 'announcement',
            'title' => $this->title,
            'message' => $this->message,
            'link' => $this->link,
            'data' => [],
        ];
    }
}
