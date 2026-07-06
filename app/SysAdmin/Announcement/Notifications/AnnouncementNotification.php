<?php

declare(strict_types=1);

namespace App\SysAdmin\Announcement\Notifications;

use App\Core\Channels\CustomDatabaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
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
        return ['mail', 'broadcast', CustomDatabaseChannel::class];
    }

    public function toMail($notifiable): MailMessage
    {
        return new MailMessage()
            ->subject($this->title)
            ->greeting(__('Hello!'))
            ->line($this->message)
            ->when(
                $this->link,
                fn($m) => $m->action(__('notifications.ui.view_details'), url($this->link)),
            );
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
