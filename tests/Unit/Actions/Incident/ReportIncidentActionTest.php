<?php

declare(strict_types=1);

use App\Actions\Incident\ReportIncidentAction;
use App\Models\IncidentReport;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('reports an incident', function () {
        $user = User::factory()->create();
        $registration = Registration::factory()->create(['status' => 'active']);

        $incident = app(ReportIncidentAction::class)->execute([
            'registration_id' => $registration->id,
            'reported_by' => $user->id,
            'incident_date' => now()->format('Y-m-d H:i:s'),
            'type' => 'accident',
            'severity' => 'high',
            'description' => 'Student fell and injured their ankle at the workshop.',
        ]);

        expect($incident)->toBeInstanceOf(IncidentReport::class)
            ->and($incident->status->value)->toBe('reported')
            ->and($incident->severity->value)->toBe('high');
    });

    it('validates required fields', function () {
        app(ReportIncidentAction::class)->execute([]);
    })->throws(ValidationException::class);
});
