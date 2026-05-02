<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Notifications\Channels\CustomDatabaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubmissionFeedbackNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $assignmentTitle,
        public string $status,
        public ?string $feedback = null
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'broadcast', CustomDatabaseChannel::class];
    }

    public function toBroadcast($notifiable): array
    {
        return [
            'title' => __('notifications.submission_feedback.title'),
            'message' => __('notifications.submission_feedback.broadcast', [
                'title' => $this->assignmentTitle,
                'status' => strtoupper($this->status),
            ]),
            'link' => '/student/dashboard',
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject(__('notifications.submission_feedback.mail_subject', ['title' => $this->assignmentTitle]))
            ->greeting(__('notifications.welcome.mail_greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.submission_feedback.mail_line1', [
                'title' => $this->assignmentTitle,
                'status' => strtoupper($this->status),
            ]));

        if ($this->feedback) {
            $message->line(__('notifications.submission_feedback.mail_feedback', ['feedback' => $this->feedback]));
        }

        return $message->action(__('common.setup_required.action', default: 'View Submission'), url('/student/dashboard'));
    }

    public function toCustomDatabase($notifiable): array
    {
        return [
            'type' => 'submission_feedback',
            'title' => __('notifications.submission_feedback.title'),
            'message' => __('notifications.submission_feedback.database', [
                'title' => $this->assignmentTitle,
                'status' => strtoupper($this->status),
            ]),
            'link' => '/student/dashboard',
            'data' => [
                'assignment_title' => $this->assignmentTitle,
                'status' => $this->status,
                'feedback' => $this->feedback,
            ],
        ];
    }
}
