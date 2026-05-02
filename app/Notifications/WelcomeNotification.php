<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Notifications\Channels\CustomDatabaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $temporaryPassword = ''
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'broadcast', CustomDatabaseChannel::class];
    }

    public function toBroadcast($notifiable): array
    {
        return [
            'title' => __('notifications.welcome.title'),
            'message' => __('notifications.welcome.broadcast'),
            'link' => '/profile',
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject(__('notifications.welcome.mail_subject'))
            ->greeting(__('notifications.welcome.mail_greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.welcome.mail_line1'))
            ->line(__('notifications.welcome.mail_username', ['username' => $notifiable->username]));

        if ($this->temporaryPassword) {
            $message->line(__('notifications.welcome.mail_password', ['password' => $this->temporaryPassword]))
                ->line(__('notifications.welcome.mail_line2'));
        }

        return $message->action(__('notifications.welcome.mail_action'), url('/login'))
            ->line(__('notifications.welcome.mail_line3', default: 'Thank you for using our application!'));
    }

    public function toCustomDatabase($notifiable): array
    {
        return [
            'type' => 'system_welcome',
            'title' => __('notifications.welcome.title'),
            'message' => __('notifications.welcome.database'),
            'link' => '/profile',
            'data' => [],
        ];
    }
}
