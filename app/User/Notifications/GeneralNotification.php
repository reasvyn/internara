<?php

declare(strict_types=1);

namespace App\User\Notifications;

use App\Core\Channels\CustomDatabaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GeneralNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $type,
        public string $title,
        public string $message,
        public ?string $link = null,
        public ?array $data = null,
        public bool $sendEmail = true,
    ) {}

    public function via($notifiable): array
    {
        $channels = [CustomDatabaseChannel::class];

        if ($this->sendEmail) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        return new MailMessage()
            ->subject($this->title)
            ->line($this->message)
            ->when(
                $this->link,
                fn ($m) => $m->action(
                    __('notifications.ui.view_details', [], 'id'),
                    url($this->link),
                ),
            );
    }

    public function toCustomDatabase($notifiable): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'link' => $this->link,
            'data' => $this->data ?? [],
        ];
    }
}
