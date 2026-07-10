<?php

declare(strict_types=1);

use App\Enrollment\Registration\Models\Registration;
use App\Incident\IncidentReport\Enums\IncidentSeverity;
use App\Incident\IncidentReport\Enums\IncidentStatus;
use App\Incident\IncidentReport\Enums\IncidentType;
use App\Incident\IncidentReport\Models\IncidentReport;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('incident report belongs to registration', function () {
    $registration = Registration::factory()->create();
    $report = IncidentReport::factory()->create(['registration_id' => $registration->id]);

    expect($report->registration)->toBeInstanceOf(Registration::class);
});

test('incident report belongs to reporter', function () {
    $reporter = User::factory()->create();
    $report = IncidentReport::factory()->create(['reported_by' => $reporter->id]);

    expect($report->reporter)->toBeInstanceOf(User::class);
});

test('default status is reported', function () {
    $report = IncidentReport::factory()->create();

    expect($report->status)->toBeInstanceOf(IncidentStatus::class);
    expect($report->status->value)->toBe('reported');
});

test('casts enums correctly', function () {
    $report = IncidentReport::factory()->create();

    expect($report->type)->toBeInstanceOf(IncidentType::class);
    expect($report->severity)->toBeInstanceOf(IncidentSeverity::class);
    expect($report->status)->toBeInstanceOf(IncidentStatus::class);
});
