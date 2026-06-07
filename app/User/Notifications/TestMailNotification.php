<?php

declare(strict_types=1);

namespace App\User\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TestMailNotification extends Notification
{
    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return new MailMessage()
            ->subject(__('setting.test_mail.subject'))
            ->greeting(__('setting.test_mail.greeting'))
            ->line(__('setting.test_mail.line1'))
            ->line(__('setting.test_mail.line2'))
            ->action(__('setting.test_mail.action'), url('/admin/settings'));
    }
}
