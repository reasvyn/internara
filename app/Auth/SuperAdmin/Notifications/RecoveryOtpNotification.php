<?php

declare(strict_types=1);

namespace App\Auth\SuperAdmin\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RecoveryOtpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $otp) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return new MailMessage()
            ->subject(__('notifications.recovery_otp.mail_subject'))
            ->greeting(__('notifications.recovery_otp.mail_greeting'))
            ->line(__('notifications.recovery_otp.mail_line1'))
            ->line($this->otp)
            ->line(__('notifications.recovery_otp.mail_line2'))
            ->line(__('notifications.recovery_otp.mail_line3'));
    }
}
