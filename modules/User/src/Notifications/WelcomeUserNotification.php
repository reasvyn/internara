<?php

declare(strict_types=1);

namespace Modules\User\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeUserNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(protected string $plainPassword) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // We want to send an email and also store it in the database.
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $brandName = setting('brand_name', setting('app_name'));

        return (new MailMessage)
            ->subject(__('user::notifications.welcome_subject', ['school' => $brandName]))
            ->greeting(__('user::notifications.welcome_greeting', ['name' => $notifiable->name]))
            ->line(__('user::notifications.welcome_line_1', ['school' => $brandName]))
            ->line(__('user::notifications.welcome_credentials_info'))
            ->line(
                __('user::notifications.welcome_username', ['username' => $notifiable->username]),
            )
            ->line(__('user::notifications.welcome_password', ['password' => $this->plainPassword]))
            ->line(__('user::notifications.welcome_line_2'))
            ->action(__('user::notifications.welcome_action'), route('profile.index'))
            ->line(__('user::notifications.welcome_line_3'))
            ->salutation(__('auth::emails.verification_salutation', ['school' => $brandName]));
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => __('user::notifications.welcome_db_message', [
                'school' => setting('brand_name', setting('app_name')),
            ]),
            'action_url' => route('profile.index'),
            'sender_name' => setting('brand_name', setting('app_name')) . ' Team',
        ];
    }
}
