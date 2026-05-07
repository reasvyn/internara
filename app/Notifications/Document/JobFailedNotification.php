<?php

declare(strict_types=1);

namespace App\Notifications\Document;

use App\Channels\Notification\CustomDatabaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JobFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $errorMessage,
        public string $link = '/dashboard',
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'broadcast', CustomDatabaseChannel::class];
    }

    public function toBroadcast($notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->errorMessage,
            'link' => $this->link,
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.job_failed.mail_subject', ['title' => $this->title]))
            ->greeting(__('notifications.welcome.mail_greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.job_failed.mail_line1', ['title' => $this->title]))
            ->line(__('notifications.job_failed.mail_error', ['error' => $this->errorMessage]))
            ->action(
                __('common.setup_required.action', default: 'View Details'),
                url($this->link),
            );
    }

    public function toCustomDatabase($notifiable): array
    {
        return [
            'type' => 'job_failed',
            'title' => $this->title,
            'message' => $this->errorMessage,
            'link' => $this->link,
            'data' => [
                'error' => $this->errorMessage,
            ],
        ];
    }
}
