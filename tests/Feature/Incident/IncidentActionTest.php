<?php

declare(strict_types=1);

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Incident\Domain\IncidentReport\Actions\ReportIncidentAction;
use App\Modules\Incident\Domain\IncidentReport\Actions\ResolveIncidentAction;
use App\Modules\Incident\Domain\IncidentReport\Actions\UpdateIncidentAction;
use App\Modules\Incident\Domain\IncidentReport\Enums\IncidentSeverity;
use App\Modules\Incident\Domain\IncidentReport\Enums\IncidentStatus;
use App\Modules\Incident\Domain\IncidentReport\Enums\IncidentType;
use App\Modules\Incident\Domain\IncidentReport\Models\IncidentReport;
use App\Modules\Incident\Domain\IncidentReport\Notifications\IncidentReportedNotification;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

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

describe('3RU9S: incident command actions and behavior', function (): void {
    test('3RU9S-FR-INC-001: IncidentReport model contract defines UUIDv7 PK, fillable whitelist, typed casts, and relations', function (): void {
        $registration = Registration::factory()->create();
        $reporter = User::factory()->create();
        $resolver = User::factory()->create();

        $incident = IncidentReport::create([
            'registration_id' => $registration->id,
            'reported_by' => $reporter->id,
            'incident_date' => now(),
            'type' => IncidentType::ACCIDENT,
            'severity' => IncidentSeverity::HIGH,
            'description' => 'Chemical spill occurred in laboratory room B during morning practical session.',
            'location' => 'Lab B',
            'action_taken' => 'Immediate evacuation completed',
            'status' => IncidentStatus::RESOLVED,
            'resolved_by' => $resolver->id,
            'resolved_at' => now(),
            'resolution_notes' => 'Chemical neutralized safely and certified clear.',
        ]);

        expect($incident->getKey())->toBeString()
            ->and(strlen($incident->getKey()))->toBe(36)
            ->and($incident->type)->toBe(IncidentType::ACCIDENT)
            ->and($incident->severity)->toBe(IncidentSeverity::HIGH)
            ->and($incident->status)->toBe(IncidentStatus::RESOLVED)
            ->and($incident->incident_date)->toBeInstanceOf(Carbon::class)
            ->and($incident->resolved_at)->toBeInstanceOf(Carbon::class)
            ->and($incident->registration->is($registration))->toBeTrue()
            ->and($incident->reporter->is($reporter))->toBeTrue()
            ->and($incident->resolver->is($resolver))->toBeTrue();
    });

    test('3RU9S-FR-INC-002, 3RU9S-FR-INC-003, 3RU9S-FR-INC-005, 3RU9S-FR-INC-007: report action persists a reported incident and returns it', function (): void {
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

    test('3RU9S-FR-INC-007, 3RU9S-FR-INC-016: report action rejects invalid payload before persistence', function (): void {
        $registration = Registration::factory()->create();
        $reporter = User::factory()->create();

        expect(fn () => app(ReportIncidentAction::class)->execute(
            incidentPayload($registration, $reporter, ['type' => 'unknown']),
        ))->toThrow(ValidationException::class);

        expect(IncidentReport::count())->toBe(0);
    });

    test('3RU9S-FR-INC-010, 3RU9S-FR-INC-016: update action changes only supplied validated fields', function (): void {
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

    test('3RU9S-FR-INC-012, 3RU9S-FR-INC-013, 3RU9S-NFR-INC-002: resolve action atomically records resolver, notes, and timestamp', function (): void {
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

        $incomplete = IncidentReport::where('status', 'resolved')
            ->where(function ($query) {
                $query->whereNull('resolved_by')
                    ->orWhereNull('resolved_at')
                    ->orWhereNull('resolution_notes');
            })->count();
        expect($incomplete)->toBe(0);
    });

    test('3RU9S-FR-INC-012: resolve action requires resolution notes', function (): void {
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

    test('3RU9S-FR-INC-014: RESOLVED closes to CLOSED only through the guarded update path', function (): void {
        $incident = IncidentReport::factory()->create(['status' => IncidentStatus::RESOLVED->value]);

        $closed = app(UpdateIncidentAction::class)->execute($incident, [
            'status' => IncidentStatus::CLOSED->value,
        ]);

        expect($closed->status)->toBe(IncidentStatus::CLOSED);
        $this->assertDatabaseHas('incident_reports', [
            'id' => $incident->id,
            'status' => 'closed',
        ]);
    });

    test('3RU9S-FR-INC-015, 3RU9S-NFR-INC-004, 3RU9S-DD-INC-003: queued incident notification dispatches after commit for high and critical severities', function (): void {
        Notification::fake();
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $registration = Registration::factory()->create();
        $reporter = User::factory()->create();

        // Failed transaction produces zero notifications (no ghost notifications)
        try {
            app(ReportIncidentAction::class)->execute(
                incidentPayload($registration, $reporter, ['severity' => 'critical', 'type' => 'invalid_type']),
            );
        } catch (Throwable) {
        }
        Notification::assertNothingSent();

        // Committed write queues notification
        $incident = app(ReportIncidentAction::class)->execute(
            incidentPayload($registration, $reporter, ['severity' => 'critical']),
        );

        Notification::assertSentTo($admin, IncidentReportedNotification::class);
    });

    test('3RU9S-FR-INC-018: all user-facing incident strings are bilingual and mutations audit log', function (): void {
        foreach (['en', 'id'] as $locale) {
            app()->setLocale($locale);
            expect(__('incident.title'))->not->toBe('incident.title')
                ->and(__('incident.report_success'))->not->toBe('incident.report_success')
                ->and(__('incident.illegal_transition', ['from' => 'A', 'to' => 'B']))->not->toBe('incident.illegal_transition');
        }

        $registration = Registration::factory()->create();
        $reporter = User::factory()->create();
        $this->actingAs($reporter);
        $incident = app(ReportIncidentAction::class)->execute(
            incidentPayload($registration, $reporter, ['description' => 'Logged incident description']),
        );

        $activity = Activity::where('event', 'incident_reported')->latest('id')->first();
        expect($activity)->not->toBeNull()
            ->and($activity->subject_id)->toBe($incident->id);
    });

    test('3RU9S-NFR-INC-001, 3RU9S-DD-INC-001: status moves outside the transition map are rejected before persistence', function (): void {
        $reported = IncidentReport::factory()->create(['status' => IncidentStatus::REPORTED->value]);

        // REPORTED -> CLOSED is illegal
        expect(fn () => app(UpdateIncidentAction::class)->execute($reported, [
            'status' => IncidentStatus::CLOSED->value,
        ]))->toThrow(RejectedException::class);
        expect($reported->fresh()->status)->toBe(IncidentStatus::REPORTED);

        // RESOLVED -> INVESTIGATING is illegal
        $resolved = IncidentReport::factory()->create(['status' => IncidentStatus::RESOLVED->value]);
        expect(fn () => app(UpdateIncidentAction::class)->execute($resolved, [
            'status' => IncidentStatus::INVESTIGATING->value,
        ]))->toThrow(RejectedException::class);
        expect($resolved->fresh()->status)->toBe(IncidentStatus::RESOLVED);

        // CLOSED -> anything is illegal
        $closed = IncidentReport::factory()->create(['status' => IncidentStatus::CLOSED->value]);
        expect(fn () => app(UpdateIncidentAction::class)->execute($closed, [
            'status' => IncidentStatus::REPORTED->value,
        ]))->toThrow(RejectedException::class);
        expect($closed->fresh()->status)->toBe(IncidentStatus::CLOSED);
    });

    test('3RU9S-NFR-INC-003: registration FK cascade deletes incident reports leaving zero orphan records', function (): void {
        $registration = Registration::factory()->create();
        $reporter = User::factory()->create();
        $incident = IncidentReport::factory()->create([
            'registration_id' => $registration->id,
            'reported_by' => $reporter->id,
        ]);

        $this->assertDatabaseHas('incident_reports', ['id' => $incident->id]);
        $registration->delete();
        $this->assertDatabaseMissing('incident_reports', ['id' => $incident->id]);
    });

    test('3RU9S-NFR-INC-005: schema defines composite registration_status index and property indexes', function (): void {
        $indexes = collect(Schema::getIndexes('incident_reports'))
            ->map(fn ($idx) => $idx['columns'])
            ->all();

        expect($indexes)->toContain(['registration_id', 'status'])
            ->and($indexes)->toContain(['type'])
            ->and($indexes)->toContain(['severity'])
            ->and($indexes)->toContain(['status']);
    });

    test('3RU9S-NFR-INC-006: incident enums render human readable labels in the reader locale', function (): void {
        app()->setLocale('id');
        expect(IncidentStatus::REPORTED->label())->toBe('Dilaporkan')
            ->and(IncidentStatus::REPORTED->label())->not->toBe(IncidentStatus::REPORTED->value)
            ->and(IncidentSeverity::CRITICAL->label())->toBe('Kritis')
            ->and(IncidentType::ACCIDENT->label())->toBe('Kecelakaan');

        app()->setLocale('en');
        expect(IncidentStatus::REPORTED->label())->toBe('Reported')
            ->and(IncidentSeverity::CRITICAL->label())->toBe('Critical')
            ->and(IncidentType::ACCIDENT->label())->toBe('Accident');
    });

    test('3RU9S-DD-INC-004: location and immediate action fields stay nullable at filing', function (): void {
        $registration = Registration::factory()->create();
        $reporter = User::factory()->create();

        $incident = app(ReportIncidentAction::class)->execute(
            incidentPayload($registration, $reporter, [
                'location' => null,
                'action_taken' => null,
            ]),
        );

        expect($incident->location)->toBeNull()
            ->and($incident->action_taken)->toBeNull();
        $this->assertDatabaseHas('incident_reports', [
            'id' => $incident->id,
            'location' => null,
            'action_taken' => null,
        ]);
    });

    test('3RU9S-UC-INC-002: admin opens an investigation on a reported incident', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $incident = IncidentReport::factory()->create(['status' => IncidentStatus::REPORTED->value]);
        $updated = app(UpdateIncidentAction::class)->execute($incident, [
            'status' => IncidentStatus::INVESTIGATING->value,
        ]);

        expect($updated->status)->toBe(IncidentStatus::INVESTIGATING);
        $this->assertDatabaseHas('incident_reports', [
            'id' => $incident->id,
            'status' => 'investigating',
        ]);
    });

    test('3RU9S-UC-INC-004: admin raises an incident severity as investigation reveals more', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $incident = IncidentReport::factory()->create([
            'status' => IncidentStatus::INVESTIGATING->value,
            'severity' => IncidentSeverity::MEDIUM->value,
        ]);

        $updated = app(UpdateIncidentAction::class)->execute($incident, [
            'severity' => IncidentSeverity::CRITICAL->value,
            'description' => 'Medical assessment confirmed deep laceration requiring immediate surgical attention.',
        ]);

        expect($updated->severity)->toBe(IncidentSeverity::CRITICAL)
            ->and($updated->description)->toContain('surgical attention');
        $this->assertDatabaseHas('incident_reports', [
            'id' => $incident->id,
            'severity' => 'critical',
        ]);
    });
});
