<?php

declare(strict_types=1);

namespace App\Auth\Notifications;

use App\User\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class CredentialChangedNotification extends Notification
{
    public function __construct(
        private readonly string $changeType,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('auth.notifications.credential_changed_subject'))
            ->greeting(
                __('auth.notifications.credential_changed_greeting', ['name' => $notifiable->name]),
            )
            ->line(__("auth.notifications.{$this->changeType}_changed_line"))
            ->line(
                __('auth.notifications.credential_changed_warning', [
                    'support_email' => config('mail.from.address'),
                ]),
            );
    }
}
