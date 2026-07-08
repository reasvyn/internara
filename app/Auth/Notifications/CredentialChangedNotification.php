<?php

declare(strict_types=1);

namespace App\Auth\Notifications;

use App\Settings\Services\Settings;
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
        $supportEmail = Settings::get('support_email', '');

        $message = (new MailMessage)
            ->subject(__('auth.notifications.credential_changed_subject'))
            ->greeting(
                __('auth.notifications.credential_changed_greeting', ['name' => $notifiable->name]),
            )
            ->line(__("auth.notifications.{$this->changeType}_changed_line"));

        if ($supportEmail !== '') {
            $message->line(
                __('auth.notifications.credential_changed_warning_with_email', [
                    'support_email' => $supportEmail,
                ]),
            );
        } else {
            $message->line(__('auth.notifications.credential_changed_warning'));
        }

        return $message;
    }
}
