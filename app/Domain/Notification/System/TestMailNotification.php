<?php

declare(strict_types=1);

namespace App\Domain\Notification\System;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Test Notification to verify SMTP settings.
 *
 * S2 - Sustain: Uses brand() helper for dynamic naming.
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
        $appName = brand('name');

        return (new MailMessage)
            ->subject("{$appName}: Test SMTP Configuration")
            ->greeting('Hello, Admin!')
            ->line("This is a test email sent from {$appName} to verify your SMTP configuration.")
            ->line('If you are reading this, your email settings are working correctly.')
            ->action('Go to Dashboard', url('/dashboard'))
            ->line("Thank you for using {$appName}!");
    }
}
