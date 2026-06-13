<?php

declare(strict_types=1);

use App\SysAdmin\Observability\Recorders\SystemRecorder;
use App\User\Models\User;
use App\User\Notifications\Models\Notification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Pulse\Facades\Pulse;

uses(LazilyRefreshDatabase::class);

test('records system snapshots via pulse', function () {
    User::factory()->count(5)->create();
    Notification::factory()->create(['is_read' => false]);

    Pulse::shouldReceive('record')
        ->with('users_total', 'all', 5)
        ->once()
        ->andReturnSelf();
    Pulse::shouldReceive('record')
        ->with('notifications_unread', 'all', 1)
        ->once()
        ->andReturnSelf();

    Pulse::shouldReceive('count')->andReturnSelf();

    SystemRecorder::recordSnapshot();
});

test('records zero values when no data exists', function () {
    Pulse::shouldReceive('record')
        ->with('users_total', 'all', 0)
        ->once()
        ->andReturnSelf();
    Pulse::shouldReceive('record')
        ->with('notifications_unread', 'all', 0)
        ->once()
        ->andReturnSelf();

    Pulse::shouldReceive('count')->andReturnSelf();

    SystemRecorder::recordSnapshot();
});
