<?php

declare(strict_types=1);

namespace App\Domain\Assignment\Notifications;

use App\Domain\Notification\Channels\CustomDatabaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssignmentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $internshipName,
        public string $assignmentTitle,
        public ?string $dueDate = null,
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'broadcast', CustomDatabaseChannel::class];
    }

    public function toBroadcast($notifiable): array
    {
        return [
            'title' => __('notifications.assignment.title'),
            'message' => __('notifications.assignment.broadcast', [
                'title' => $this->assignmentTitle,
                'internship' => $this->internshipName,
            ]),
            'link' => '/student/dashboard',
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        $message = new MailMessage()
            ->subject(
                __('notifications.assignment.mail_subject', ['title' => $this->assignmentTitle]),
            )
            ->greeting(__('notifications.welcome.mail_greeting', ['name' => $notifiable->name]))
            ->line(
                __('notifications.assignment.mail_line1', ['internship' => $this->internshipName]),
            )
            ->line(__('notifications.assignment.mail_title', ['title' => $this->assignmentTitle]));

        if ($this->dueDate) {
            $message->line(
                __('notifications.assignment.mail_due_date', ['due_date' => $this->dueDate]),
            );
        }

        return $message->action(
            __('common.setup_required.action', default: 'View Assignment'),
            url('/student/dashboard'),
        );
    }

    public function toCustomDatabase($notifiable): array
    {
        return [
            'type' => 'assignment_published',
            'title' => __('notifications.assignment.title'),
            'message' => __('notifications.assignment.database', [
                'title' => $this->assignmentTitle,
            ]),
            'link' => '/student/dashboard',
            'data' => [
                'assignment_title' => $this->assignmentTitle,
                'due_date' => $this->dueDate,
            ],
        ];
    }
}
