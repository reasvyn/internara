<?php

declare(strict_types=1);

namespace Modules\Admin\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email invitation sent to newly created Administrator accounts.
 *
 * Unlike short activation codes (printed/handed), admin accounts receive
 * a long-token link via email. Clicking it proves inbox ownership and
 * allows the admin to set their personal password.
 */
class AdminInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected readonly string $plainToken,
        protected readonly int $expiresInDays = 7,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $brand = setting('brand_name', setting('app_name'));
        $inviteUrl = route('invitation.accept', ['token' => $this->plainToken]);
        $expireText = trans_choice(
            'admin::notifications.invitation_expires_days',
            $this->expiresInDays,
            [
                'days' => $this->expiresInDays,
            ],
        );

        return new MailMessage()
            ->subject(__('admin::notifications.invitation_subject', ['school' => $brand]))
            ->greeting(
                __('admin::notifications.invitation_greeting', ['name' => $notifiable->name]),
            )
            ->line(__('admin::notifications.invitation_line_1', ['school' => $brand]))
            ->line(
                __('admin::notifications.invitation_username', [
                    'username' => $notifiable->username,
                ]),
            )
            ->line(__('admin::notifications.invitation_line_2'))
            ->action(__('admin::notifications.invitation_action'), $inviteUrl)
            ->line($expireText)
            ->line(__('admin::notifications.invitation_line_3'))
            ->salutation(__('admin::notifications.invitation_salutation', ['school' => $brand]));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => __('admin::notifications.invitation_db_message'),
            'action_url' => route('invitation.accept', ['token' => $this->plainToken]),
            'sender_name' => setting('brand_name', setting('app_name')) . ' Team',
        ];
    }
}
