<?php

declare(strict_types=1);

namespace App\Notifications\Auth;

use App\Channels\CustomDatabaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminRecoveredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $recoveredEmail,
        public string $mode,
        public string $initiatorHostname,
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'broadcast', CustomDatabaseChannel::class];
    }

    public function toBroadcast($notifiable): array
    {
        return [
            'title' => __('notifications.admin_recovered.title'),
            'message' => __('notifications.admin_recovered.broadcast', [
                'email' => $this->recoveredEmail,
                'mode' => $this->mode,
            ]),
            'link' => '/admin/users',
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.admin_recovered.mail_subject'))
            ->greeting(__('notifications.admin_recovered.mail_greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.admin_recovered.mail_line1', [
                'email' => $this->recoveredEmail,
            ]))
            ->line(__('notifications.admin_recovered.mail_line2', [
                'mode' => $this->mode,
            ]))
            ->line(__('notifications.admin_recovered.mail_line3', [
                'hostname' => $this->initiatorHostname,
            ]))
            ->line(__('notifications.admin_recovered.mail_line4'))
            ->action(__('notifications.admin_recovered.mail_action'), url('/admin/users'));
    }

    public function toCustomDatabase($notifiable): array
    {
        return [
            'type' => 'admin_recovery',
            'title' => __('notifications.admin_recovered.title'),
            'message' => __('notifications.admin_recovered.database', [
                'email' => $this->recoveredEmail,
                'mode' => $this->mode,
            ]),
            'link' => '/admin/users',
            'data' => [
                'recovered_email' => $this->recoveredEmail,
                'mode' => $this->mode,
                'hostname' => $this->initiatorHostname,
            ],
        ];
    }
}
