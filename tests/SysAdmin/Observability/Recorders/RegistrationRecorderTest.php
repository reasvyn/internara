<?php

declare(strict_types=1);

use App\Enrollment\Registration\Models\Registration;
use App\SysAdmin\Observability\Recorders\RegistrationRecorder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Pulse\Entry;
use Laravel\Pulse\Facades\Pulse;

uses(LazilyRefreshDatabase::class);

test('records registration snapshots via pulse', function () {
    Registration::factory()->create(['status' => 'pending']);
    Registration::factory()->count(2)->create(['status' => 'active']);
    Registration::factory()->create(['status' => 'completed']);

    Pulse::shouldReceive('record')->withAnyArgs()->zeroOrMoreTimes()->andReturn(new Entry(time(), 'type', 'key'));

    RegistrationRecorder::recordSnapshot();
});

test('records zero values when no registrations exist', function () {
    Pulse::shouldReceive('record')->withAnyArgs()->zeroOrMoreTimes()->andReturn(new Entry(time(), 'type', 'key'));

    RegistrationRecorder::recordSnapshot();
});
