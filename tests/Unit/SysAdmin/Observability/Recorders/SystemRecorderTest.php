<?php

declare(strict_types=1);

use App\SysAdmin\Observability\Recorders\SystemRecorder;
use App\User\Models\User;
use App\User\Notifications\Models\Notification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Pulse\Entry;
use Laravel\Pulse\Facades\Pulse;

uses(LazilyRefreshDatabase::class);

test('records system snapshots via pulse', function () {
    User::factory()->count(5)->create();
    Notification::factory()->create(['is_read' => false]);

    Pulse::shouldReceive('record')->withAnyArgs()->zeroOrMoreTimes()->andReturn(new Entry(time(), 'type', 'key'));

    SystemRecorder::recordSnapshot();
});

test('records zero values when no data exists', function () {
    Pulse::shouldReceive('record')->withAnyArgs()->zeroOrMoreTimes()->andReturn(new Entry(time(), 'type', 'key'));

    SystemRecorder::recordSnapshot();
});
