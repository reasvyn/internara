<?php

declare(strict_types=1);

namespace App\Program\Internship\Notifications;

use App\Core\Channels\CustomDatabaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InternshipCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $internshipName,
        public ?string $createdByName = null,
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'broadcast', CustomDatabaseChannel::class];
    }

    public function toBroadcast($notifiable): array
    {
        return [
            'title' => __('notifications.internship_created.title'),
            'message' => __('notifications.internship_created.broadcast', [
                'name' => $this->internshipName,
            ]),
            'link' => '/admin/internships',
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return new MailMessage()
            ->subject(__('notifications.internship_created.mail_subject'))
            ->greeting(__('notifications.welcome.mail_greeting', ['name' => $notifiable->name]))
            ->line(
                __('notifications.internship_created.mail_line1', [
                    'name' => $this->internshipName,
                ]),
            )
            ->action(__('notifications.internship_created.mail_action'), url('/admin/internships'));
    }

    public function toCustomDatabase($notifiable): array
    {
        return [
            'type' => 'internship_created',
            'title' => __('notifications.internship_created.title'),
            'message' => __('notifications.internship_created.database', [
                'name' => $this->internshipName,
            ]),
            'link' => '/admin/internships',
            'data' => [
                'internship_name' => $this->internshipName,
                'created_by' => $this->createdByName,
            ],
        ];
    }
}
