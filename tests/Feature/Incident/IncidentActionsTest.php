<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\Incident\Actions\ReportIncidentAction;
use App\Domain\Incident\Actions\ResolveIncidentAction;
use App\Domain\Incident\Actions\UpdateIncidentAction;
use App\Domain\Incident\Models\IncidentReport;
use App\Domain\Mentee\Models\Mentee;
use App\Domain\Registration\Models\Registration;
use App\Domain\User\Models\User;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    RoleModel::create(['name' => Role::SUPER_ADMIN->value, 'guard_name' => 'web']);
});

describe('ReportIncidentAction', function () {
    it('reports an incident', function () {
        $reporter = User::factory()->create();
        $mentee = Mentee::factory()->create();
        $registration = Registration::factory()->create(['mentee_id' => $mentee->id]);

        $incident = app(ReportIncidentAction::class)->execute([
            'registration_id' => $registration->id,
            'reported_by' => $reporter->id,
            'incident_date' => now()->toDateString(),
            'type' => 'accident',
            'severity' => 'low',
            'description' => 'A minor accident occurred',
        ]);

        expect($incident)->toBeInstanceOf(IncidentReport::class)
            ->and($incident->type->value)->toBe('accident')
            ->and($incident->severity->value)->toBe('low')
            ->and($incident->status->value)->toBe('reported');
    });

    it('reports a critical incident without throwing when no admins exist', function () {
        $reporter = User::factory()->create();
        $mentee = Mentee::factory()->create();
        $registration = Registration::factory()->create(['mentee_id' => $mentee->id]);

        $incident = app(ReportIncidentAction::class)->execute([
            'registration_id' => $registration->id,
            'reported_by' => $reporter->id,
            'incident_date' => now()->toDateString(),
            'type' => 'harassment',
            'severity' => 'critical',
            'description' => 'Critical incident',
        ]);

        expect($incident->severity->value)->toBe('critical');
    });

    it('validates required fields', function () {
        app(ReportIncidentAction::class)->execute([]);
    })->throws(ValidationException::class);
});

describe('UpdateIncidentAction', function () {
    it('updates an incident report', function () {
        $reporter = User::factory()->create();
        $mentee = Mentee::factory()->create();
        $registration = Registration::factory()->create(['mentee_id' => $mentee->id]);
        $incident = IncidentReport::create([
            'registration_id' => $registration->id,
            'reported_by' => $reporter->id,
            'incident_date' => now(),
            'type' => 'other',
            'severity' => 'low',
            'description' => 'Original description',
        ]);

        $updated = app(UpdateIncidentAction::class)->execute($incident, [
            'description' => 'Updated description',
            'status' => 'investigating',
        ]);

        expect($updated->description)->toBe('Updated description')
            ->and($updated->status->value)->toBe('investigating');
    });
});

describe('ResolveIncidentAction', function () {
    it('resolves an incident', function () {
        $admin = User::factory()->create();
        $admin->assignRole(Role::SUPER_ADMIN->value);
        $this->actingAs($admin);

        $reporter = User::factory()->create();
        $mentee = Mentee::factory()->create();
        $registration = Registration::factory()->create(['mentee_id' => $mentee->id]);
        $incident = IncidentReport::create([
            'registration_id' => $registration->id,
            'reported_by' => $reporter->id,
            'incident_date' => now(),
            'type' => 'accident',
            'severity' => 'high',
            'description' => 'Incident to resolve',
        ]);

        $resolved = app(ResolveIncidentAction::class)->execute($incident, [
            'resolution_notes' => 'Issue resolved after investigation',
            'status' => 'resolved',
        ]);

        expect($resolved->status->value)->toBe('resolved')
            ->and($resolved->resolution_notes)->toBe('Issue resolved after investigation')
            ->and($resolved->resolved_by)->toBe($admin->id);
    });

    it('throws when incident is already closed', function () {
        $reporter = User::factory()->create();
        $mentee = Mentee::factory()->create();
        $registration = Registration::factory()->create(['mentee_id' => $mentee->id]);
        $incident = IncidentReport::create([
            'registration_id' => $registration->id,
            'reported_by' => $reporter->id,
            'incident_date' => now(),
            'type' => 'other',
            'severity' => 'low',
            'description' => 'Closed incident',
            'status' => 'closed',
        ]);

        app(ResolveIncidentAction::class)->execute($incident, [
            'resolution_notes' => 'Cannot resolve',
            'status' => 'resolved',
        ]);
    })->throws(RuntimeException::class, 'already closed');
});
