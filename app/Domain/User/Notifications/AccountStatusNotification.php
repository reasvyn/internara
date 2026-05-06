<?php

declare(strict_types=1);

namespace App\Domain\User\Notifications;

use App\Domain\Notification\Channels\CustomDatabaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifies users when their account status changes (lock, unlock, suspend, activate).
 *
 * Sends notifications via multiple channels to ensure users are informed
 * of any changes to their account access.
 */
class AccountStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param string $status The new account status (locked, unlocked, suspended, activated)
     * @param string|null $reason Optional explanation for the status change
     */
    public function __construct(public string $status, public ?string $reason = null) {}

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable The entity being notified
     *
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['mail', 'broadcast', CustomDatabaseChannel::class];
    }

    /**
     * Get the broadcastable representation of the notification.
     *
     * @param mixed $notifiable The entity being notified
     *
     * @return array<string, mixed>
     */
    public function toBroadcast($notifiable): array
    {
        return [
            'title' => __('notifications.account_status.title'),
            'message' => __('notifications.account_status.broadcast', [
                'status' => strtoupper($this->status),
            ]),
            'link' => '/profile',
        ];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable The entity being notified
     */
    public function toMail($notifiable): MailMessage
    {
        $message = new MailMessage()
            ->subject(__('notifications.account_status.mail_subject'))
            ->greeting(__('notifications.welcome.mail_greeting', ['name' => $notifiable->name]))
            ->line(
                __('notifications.account_status.mail_line1', [
                    'status' => strtoupper($this->status),
                ]),
            );

        if ($this->reason) {
            $message->line(
                __('notifications.account_status.mail_reason', ['reason' => $this->reason]),
            );
        }

        return $message->action(__('notifications.welcome.mail_action'), url('/login'));
    }

    /**
     * Get the storable array representation for database storage.
     *
     * @param mixed $notifiable The entity being notified
     *
     * @return array<string, mixed>
     */
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
