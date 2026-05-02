<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Test Notification to verify SMTP settings.
 */
class TestMailNotification extends Notification
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Internara: Test SMTP Configuration')
            ->greeting('Hello, Admin!')
            ->line('This is a test email sent from Internara to verify your SMTP configuration.')
            ->line('If you are reading this, your email settings are working correctly.')
            ->action('Go to Dashboard', url('/dashboard'))
            ->line('Thank you for using Internara!');
    }
}
