<?php

declare(strict_types=1);

use App\Incident\IncidentReport\Models\IncidentReport;
use App\Core\Channels\CustomDatabaseChannel;
use App\Incident\IncidentReport\Notifications\IncidentReportedNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('notification constructs with incident data', function () {
    $incident = IncidentReport::factory()->make();
    $notification = new IncidentReportedNotification($incident);

    expect($notification->incident)->toBeInstanceOf(IncidentReport::class);
});

test('notification via channels', function () {
    $incident = IncidentReport::factory()->make();
    $notification = new IncidentReportedNotification($incident);

    expect($notification->via(new stdClass))->toBe(['mail', 'broadcast', CustomDatabaseChannel::class]);
});
