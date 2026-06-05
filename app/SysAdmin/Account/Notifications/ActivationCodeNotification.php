<?php

declare(strict_types=1);

namespace App\SysAdmin\Account\Notifications;

use App\Core\Channels\CustomDatabaseChannel;
use App\User\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ActivationCodeNotification extends Notification
{
    public function __construct(
        public readonly User $user,
        public readonly string $code,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', CustomDatabaseChannel::class];
    }

    public function toMail(object $notifiable): object
    {
        return (new MailMessage)
            ->subject(__('user.activation.email_subject'))
            ->greeting(__('user.activation.email_greeting', ['name' => $this->user->name]))
            ->line(__('user.activation.email_intro'))
            ->line($this->code)
            ->action(__('user.activation.email_action'), route('activate'))
            ->line(__('user.activation.email_expiry', ['days' => 30]))
            ->line(__('user.activation.email_ignore'));
    }

    public function toCustomDatabase(object $notifiable): array
    {
        return [
            'type' => 'activation_code',
            'title' => __('user.activation.notification_title'),
            'message' => __('user.activation.notification_message'),
            'link' => route('activate'),
        ];
    }
}
