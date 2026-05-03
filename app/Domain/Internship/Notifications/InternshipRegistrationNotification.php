<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Notifications\Channels\CustomDatabaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $internshipName, public string $status) {}

    public function via($notifiable): array
    {
        return ['mail', 'broadcast', CustomDatabaseChannel::class];
    }

    public function toBroadcast($notifiable): array
    {
        return [
            'title' => __('notifications.internship_registration.title'),
            'message' => __('notifications.internship_registration.message', [
                'internship' => $this->internshipName,
                'status' => strtoupper($this->status),
            ]),
            'link' => '/student/dashboard',
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return new MailMessage()
            ->subject(__('notifications.internship_registration.mail_subject'))
            ->greeting(__('notifications.welcome.mail_greeting', ['name' => $notifiable->name]))
            ->line(
                __('notifications.internship_registration.mail_line1', [
                    'internship' => $this->internshipName,
                    'status' => strtoupper($this->status),
                ]),
            )
            ->action(
                __('common.setup_required.action', default: 'View Dashboard'),
                url('/student/dashboard'),
            );
    }

    public function toCustomDatabase($notifiable): array
    {
        return [
            'type' => 'internship_registration_update',
            'title' => __('notifications.internship_registration.title'),
            'message' => __('notifications.internship_registration.message', [
                'internship' => $this->internshipName,
                'status' => strtoupper($this->status),
            ]),
            'link' => '/student/dashboard',
            'data' => [
                'internship_name' => $this->internshipName,
                'status' => $this->status,
            ],
        ];
    }
}
