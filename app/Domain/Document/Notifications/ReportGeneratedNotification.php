<?php

declare(strict_types=1);

namespace App\Domain\Document\Notifications;

use App\Domain\Notification\Channels\CustomDatabaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportGeneratedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $reportType, public string $reportId) {}

    public function via($notifiable): array
    {
        return ['mail', 'broadcast', CustomDatabaseChannel::class];
    }

    public function toBroadcast($notifiable): array
    {
        $readableType = ucwords(str_replace('_', ' ', $this->reportType));

        return [
            'title' => __('notifications.report_generated.title'),
            'message' => __('notifications.report_generated.message', ['type' => $readableType]),
            'link' => '/admin/reports',
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        $readableType = ucwords(str_replace('_', ' ', $this->reportType));

        return new MailMessage()
            ->subject(__('notifications.report_generated.mail_subject', ['type' => $readableType]))
            ->greeting(__('notifications.welcome.mail_greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.report_generated.mail_line1', ['type' => $readableType]))
            ->action(
                __('common.setup_required.action', default: 'View Reports'),
                url('/admin/reports'),
            );
    }

    public function toCustomDatabase($notifiable): array
    {
        $readableType = ucwords(str_replace('_', ' ', $this->reportType));

        return [
            'type' => 'report_generated',
            'title' => __('notifications.report_generated.title'),
            'message' => __('notifications.report_generated.message', ['type' => $readableType]),
            'link' => '/admin/reports',
            'data' => [
                'report_type' => $this->reportType,
                'report_id' => $this->reportId,
            ],
        ];
    }
}
