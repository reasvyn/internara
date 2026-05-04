<?php

declare(strict_types=1);

namespace App\Domain\Notification\System;

use App\Domain\Notification\Channels\CustomDatabaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class JobFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $taskName,
        public string $errorMessage = 'An unexpected error occurred during processing.',
        public ?string $link = null,
    ) {}

    public function via($notifiable): array
    {
        return ['broadcast', CustomDatabaseChannel::class];
    }

    public function toBroadcast($notifiable): array
    {
        return [
            'title' => __('notifications.job_failed.title'),
            'message' => __('notifications.job_failed.broadcast', ['task' => $this->taskName]),
            'link' => $this->link ?? '/notifications',
        ];
    }

    public function toCustomDatabase($notifiable): array
    {
        return [
            'type' => 'job_failed',
            'title' => __('notifications.job_failed.title'),
            'message' => __('notifications.job_failed.database', [
                'task' => $this->taskName,
                'error' => $this->errorMessage,
            ]),
            'link' => $this->link ?? '/notifications',
            'data' => [
                'task_name' => $this->taskName,
                'error_message' => $this->errorMessage,
            ],
        ];
    }
}
