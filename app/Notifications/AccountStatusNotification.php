<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Notifications\Channels\CustomDatabaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $status,
        public ?string $reason = null
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'broadcast', CustomDatabaseChannel::class];
    }

    public function toBroadcast($notifiable): array
    {
        return [
            'title' => __('notifications.account_status.title'),
            'message' => __('notifications.account_status.broadcast', ['status' => strtoupper($this->status)]),
            'link' => '/profile',
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject(__('notifications.account_status.mail_subject'))
            ->greeting(__('notifications.welcome.mail_greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.account_status.mail_line1', ['status' => strtoupper($this->status)]));

        if ($this->reason) {
            $message->line(__('notifications.account_status.mail_reason', ['reason' => $this->reason]));
        }

        return $message->action(__('notifications.welcome.mail_action'), url('/login'));
    }

    public function toCustomDatabase($notifiable): array
    {
        return [
            'type' => 'account_status_change',
            'title' => __('notifications.account_status.title'),
            'message' => __('notifications.account_status.database', [
                'status' => strtoupper($this->status),
                'reason' => $this->reason ?? '-',
            ]),
            'link' => '/profile',
            'data' => [
                'status' => $this->status,
                'reason' => $this->reason,
            ],
        ];
    }
}
