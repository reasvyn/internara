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
        private readonly ?string $oldValue = null,
        private readonly ?string $newValue = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $message = new MailMessage()
            ->subject(__('auth.notifications.credential_changed_subject'))
            ->greeting(
                __('auth.notifications.credential_changed_greeting', ['name' => $notifiable->name]),
            );

        if ($this->changeType === 'email' && $this->oldValue !== null && $this->newValue !== null) {
            $message->line(
                __('auth.notifications.email_changed_line', [
                    'old_value' => $this->oldValue,
                    'new_value' => $this->newValue,
                ]),
            );
        } else {
            $message->line(__("auth.notifications.{$this->changeType}_changed_line"));
        }

        if ($this->changeType !== 'email' && $this->oldValue !== null) {
            $message->line(__('auth.notifications.old_value', ['value' => $this->oldValue]));
        }

        if ($this->changeType !== 'email' && $this->newValue !== null) {
            $message->line(__('auth.notifications.new_value', ['value' => $this->newValue]));
        }

        $message->line(
            __('auth.notifications.credential_changed_warning', [
                'support_email' => config('mail.from.address'),
            ]),
        );

        return $message;
    }
}
