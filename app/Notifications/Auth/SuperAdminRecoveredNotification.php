<?php

declare(strict_types=1);

namespace App\Notifications\Auth;

use App\Channels\CustomDatabaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SuperAdminRecoveredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $recoveredEmail,
        public string $mode,
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'broadcast', CustomDatabaseChannel::class];
    }

    public function toBroadcast($notifiable): array
    {
        return [
            'title' => __('notifications.super_admin_recovered.title'),
            'message' => __('notifications.super_admin_recovered.broadcast', [
                'email' => $this->recoveredEmail,
                'mode' => $this->mode,
            ]),
            'link' => '/admin/users',
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.super_admin_recovered.mail_subject'))
            ->greeting(__('notifications.super_admin_recovered.mail_greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.super_admin_recovered.mail_line1', [
                'email' => $this->recoveredEmail,
            ]))
            ->line(__('notifications.super_admin_recovered.mail_line2', [
                'mode' => $this->mode,
            ]))
            ->line(__('notifications.super_admin_recovered.mail_line3'))
            ->action(__('notifications.super_admin_recovered.mail_action'), url('/admin/users'));
    }

    public function toCustomDatabase($notifiable): array
    {
        return [
            'type' => 'super_admin_recovery',
            'title' => __('notifications.super_admin_recovered.title'),
            'message' => __('notifications.super_admin_recovered.database', [
                'email' => $this->recoveredEmail,
                'mode' => $this->mode,
            ]),
            'link' => '/admin/users',
            'data' => [
                'recovered_email' => $this->recoveredEmail,
                'mode' => $this->mode,
            ],
        ];
    }
}
