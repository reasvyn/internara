<?php

declare(strict_types=1);

namespace Modules\Report\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportGeneratedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected string $reportTitle, protected string $filePath) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject(__('report::notifications.generated_subject'))
            ->line(__('report::notifications.generated_line_1', ['title' => $this->reportTitle]))
            ->action(__('report::notifications.download_action'), url($this->filePath))
            ->line(__('report::notifications.generated_line_2'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => __('report::notifications.generated_db_message', [
                'title' => $this->reportTitle,
            ]),
            'file_path' => $this->filePath,
            'type' => 'report',
        ];
    }
}
