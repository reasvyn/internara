<?php

declare(strict_types=1);

namespace App\Incident\IncidentReport\Notifications;

use App\Core\Channels\CustomDatabaseChannel;
use App\Incident\IncidentReport\Models\IncidentReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IncidentReportedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly IncidentReport $incident) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'broadcast', CustomDatabaseChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.incident_reported.subject', [
                'severity' => $this->incident->severity->label(),
            ]))
            ->greeting(__('notifications.incident_reported.greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.incident_reported.description', [
                'description' => $this->incident->description,
            ]))
            ->line(__('notifications.incident_reported.severity', [
                'severity' => $this->incident->severity->label(),
            ]))
            ->action(
                __('notifications.incident_reported.action'),
                route('sysadmin.incidents'),
            );
    }

    public function toBroadcast(object $notifiable): array
    {
        return [
            'incident_id' => $this->incident->id,
            'severity' => $this->incident->severity->value,
            'title' => __('notifications.incident_reported.title'),
            'message' => $this->incident->description,
            'link' => route('sysadmin.incidents'),
        ];
    }

    public function toCustomDatabase(object $notifiable): array
    {
        return [
            'type' => 'incident_reported',
            'title' => __('notifications.incident_reported.title', [
                'severity' => $this->incident->severity->label(),
            ]),
            'message' => $this->incident->description,
            'data' => [
                'incident_id' => $this->incident->id,
                'severity' => $this->incident->severity->value,
            ],
            'link' => route('sysadmin.incidents'),
        ];
    }
}
