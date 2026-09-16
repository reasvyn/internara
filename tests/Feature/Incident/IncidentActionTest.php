<?php

declare(strict_types=1);

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Incident\Domain\IncidentReport\Actions\ReportIncidentAction;
use App\Modules\Incident\Domain\IncidentReport\Actions\ResolveIncidentAction;
use App\Modules\Incident\Domain\IncidentReport\Actions\UpdateIncidentAction;
use App\Modules\Incident\Domain\IncidentReport\Enums\IncidentStatus;
use App\Modules\Incident\Domain\IncidentReport\Models\IncidentReport;
use App\Modules\Incident\Domain\IncidentReport\Notifications\IncidentReportedNotification;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

function incidentPayload(Registration $registration, User $reporter, array $overrides = []): array
{
    return array_merge([
        'registration_id' => $registration->id,
        'reported_by' => $reporter->id,
        'incident_date' => '2026-09-14 10:30:00',
        'type' => 'safety_violation',
        'severity' => 'medium',
        'description' => 'The machine guard was missing during the morning shift.',
        'location' => 'Workshop A',
        'action_taken' => 'The machine was isolated and the supervisor was informed.',
    ], $overrides);
}

describe('3RU9S: incident command actions', function (): void {
    test('3RU9S-FR-INC-002/003/005/007: report action persists a reported incident and returns it', function (): void {
        $registration = Registration::factory()->create();
        $reporter = User::factory()->create();

        $incident = app(ReportIncidentAction::class)->execute(
            incidentPayload($registration, $reporter),
        );

        expect($incident)->toBeInstanceOf(IncidentReport::class)
            ->and($incident->status)->toBe(IncidentStatus::REPORTED)
            ->and($incident->type->value)->toBe('safety_violation')
            ->and($incident->severity->value)->toBe('medium')
            ->and($incident->registration->is($registration))->toBeTrue()
            ->and($incident->reporter->is($reporter))->toBeTrue();

        $this->assertDatabaseHas('incident_reports', [
            'id' => $incident->id,
            'status' => 'reported',
            'description' => 'The machine guard was missing during the morning shift.',
        ]);
    });

    test('3RU9S-FR-INC-007/016: report action rejects invalid payload before persistence', function (): void {
        $registration = Registration::factory()->create();
        $reporter = User::factory()->create();

        expect(fn () => app(ReportIncidentAction::class)->execute(
            incidentPayload($registration, $reporter, ['type' => 'unknown']),
        ))->toThrow(ValidationException::class);

        expect(IncidentReport::count())->toBe(0);
    });

    test('3RU9S-FR-INC-010/016: update action changes only supplied validated fields', function (): void {
        $incident = IncidentReport::factory()->create([
            'description' => 'Original description.',
            'severity' => 'low',
            'location' => null,
        ]);

        $updated = app(UpdateIncidentAction::class)->execute($incident, [
            'severity' => 'high',
            'location' => 'Loading dock',
        ]);

        expect($updated->severity->value)->toBe('high')
            ->and($updated->location)->toBe('Loading dock')
            ->and($updated->description)->toBe('Original description.')
            ->and($updated->status)->toBe(IncidentStatus::REPORTED);
    });

    test('3RU9S-FR-INC-012/013: resolve action records the resolver, notes, and timestamp', function (): void {
        $resolver = User::factory()->create();
        $this->actingAs($resolver);
        $incident = IncidentReport::factory()->create(['status' => 'investigating']);

        $resolved = app(ResolveIncidentAction::class)->execute($incident, [
            'resolution_notes' => 'The guard was replaced and the safety briefing was repeated.',
            'status' => 'resolved',
        ]);

        expect($resolved->status)->toBe(IncidentStatus::RESOLVED)
            ->and($resolved->resolved_by)->toBe($resolver->id)
            ->and($resolved->resolved_at)->not->toBeNull()
            ->and($resolved->resolution_notes)->toContain('guard was replaced');
    });

    test('3RU9S-FR-INC-012/013: resolve action requires resolution notes', function (): void {
        $incident = IncidentReport::factory()->create(['status' => 'investigating']);

        expect(fn () => app(ResolveIncidentAction::class)->execute($incident, [
            'resolution_notes' => '',
            'status' => 'resolved',
        ]))->toThrow(ValidationException::class);

        expect($incident->fresh()->status)->toBe(IncidentStatus::INVESTIGATING);
    });

    test('3RU9S-FR-INC-009: resolve action rejects a closed incident', function (): void {
        $incident = IncidentReport::factory()->create(['status' => 'closed']);

        expect(fn () => app(ResolveIncidentAction::class)->execute($incident, [
            'resolution_notes' => 'Already closed.',
            'status' => 'resolved',
        ]))->toThrow(RejectedException::class);
    });

    test('3RU9S-FR-INC-015/NFR-INC-004: high-severity report notify admins', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $registration = Registration::factory()->create();
        $reporter = User::factory()->create();
        Notification::fake();

        $incident = app(ReportIncidentAction::class)->execute(
            incidentPayload($registration, $reporter, ['severity' => 'critical']),
        );

        Notification::assertSentTo($admin, IncidentReportedNotification::class);
    });
});
