<?php

declare(strict_types=1);

use App\Modules\Core\Channels\CustomDatabaseChannel;
use App\Modules\Incident\Domain\IncidentReport\Models\IncidentReport;
use App\Modules\Incident\Domain\IncidentReport\Notifications\IncidentReportedNotification;
use App\Modules\User\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('TXR2H: incident notification delivery', function (): void {
    test('TXR2H-FR-NOTIF-021: notification exposes queued mail, broadcast, and database channels', function (): void {
        $incident = IncidentReport::factory()->make(['severity' => 'critical']);
        $notification = new IncidentReportedNotification($incident);

        expect($notification)->toBeInstanceOf(ShouldQueue::class)
            ->and($notification->via(User::factory()->make()))->toBe([
                'mail',
                'broadcast',
                CustomDatabaseChannel::class,
            ]);
    });

    test('TXR2H-FR-NOTIF-021: notification payload includes incident identity and triage details', function (): void {
        $incident = IncidentReport::factory()->make([
            'severity' => 'critical',
            'description' => 'A live electrical cable was exposed.',
        ]);
        $notification = new IncidentReportedNotification($incident);
        $recipient = User::factory()->make(['name' => 'Dewi']);

        expect($notification->toBroadcast($recipient))->toMatchArray([
            'incident_id' => $incident->id,
            'severity' => 'critical',
            'message' => 'A live electrical cable was exposed.',
        ])
            ->and($notification->toCustomDatabase($recipient))->toMatchArray([
                'type' => 'incident_reported',
                'message' => 'A live electrical cable was exposed.',
                'data' => ['incident_id' => $incident->id, 'severity' => 'critical'],
            ]);
    });
});
