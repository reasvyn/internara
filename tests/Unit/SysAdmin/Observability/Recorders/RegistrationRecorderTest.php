<?php

declare(strict_types=1);

use App\Enrollment\Registration\Models\Registration;
use App\SysAdmin\Observability\Recorders\RegistrationRecorder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Pulse\Facades\Pulse;

uses(LazilyRefreshDatabase::class);

test('records registration snapshots via pulse', function () {
    Registration::factory()->create(['status' => 'pending']);
    Registration::factory()->count(2)->create(['status' => 'active']);
    Registration::factory()->create(['status' => 'completed']);

    Pulse::shouldReceive('record')
        ->with('registrations_total', 'all', 4)
        ->once()
        ->andReturnSelf();
    Pulse::shouldReceive('record')
        ->with('registrations_pending', 'all', 1)
        ->once()
        ->andReturnSelf();
    Pulse::shouldReceive('record')
        ->with('registrations_active', 'all', 2)
        ->once()
        ->andReturnSelf();
    Pulse::shouldReceive('record')
        ->with('registrations_completed', 'all', 1)
        ->once()
        ->andReturnSelf();

    Pulse::shouldReceive('count')->andReturnSelf();
    Pulse::shouldReceive('avg')->andReturnSelf();
    Pulse::shouldReceive('max')->andReturnSelf();

    RegistrationRecorder::recordSnapshot();
});

test('records zero values when no registrations exist', function () {
    Pulse::shouldReceive('record')
        ->with('registrations_total', 'all', 0)
        ->once()
        ->andReturnSelf();
    Pulse::shouldReceive('record')
        ->with('registrations_pending', 'all', 0)
        ->once()
        ->andReturnSelf();
    Pulse::shouldReceive('record')
        ->with('registrations_active', 'all', 0)
        ->once()
        ->andReturnSelf();
    Pulse::shouldReceive('record')
        ->with('registrations_completed', 'all', 0)
        ->once()
        ->andReturnSelf();

    Pulse::shouldReceive('count')->andReturnSelf();
    Pulse::shouldReceive('avg')->andReturnSelf();
    Pulse::shouldReceive('max')->andReturnSelf();

    RegistrationRecorder::recordSnapshot();
});
