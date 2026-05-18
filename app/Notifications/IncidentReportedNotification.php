<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\IncidentReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class IncidentReportedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly IncidentReport $incident) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'incident_reported',
            'incident_id' => $this->incident->id,
            'severity' => $this->incident->severity->value,
            'description' => $this->incident->description,
            'link' => route('admin.incidents'),
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function broadcastType(): string
    {
        return 'incident_reported';
    }

    public function databaseType(): string
    {
        return 'incident_reported';
    }
}
