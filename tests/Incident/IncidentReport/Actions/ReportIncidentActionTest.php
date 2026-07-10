<?php

declare(strict_types=1);

use App\Enrollment\Registration\Models\Registration;
use App\Incident\IncidentReport\Actions\ReportIncidentAction;
use App\Incident\IncidentReport\Models\IncidentReport;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

test('reports incident with valid data', function () {
    $registration = Registration::factory()->create();
    $reporter = User::factory()->create();

    $incident = app(ReportIncidentAction::class)->execute([
        'registration_id' => $registration->id,
        'reported_by' => $reporter->id,
        'incident_date' => now()->toDateString(),
        'type' => 'accident',
        'severity' => 'low',
        'description' => 'Minor accident in workshop',
    ]);

    expect($incident)->toBeInstanceOf(IncidentReport::class);
    expect($incident->type->value)->toBe('accident');
});

test('throws validation error with missing data', function () {
    app(ReportIncidentAction::class)->execute([]);
})->throws(ValidationException::class);
