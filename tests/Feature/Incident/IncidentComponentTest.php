<?php

declare(strict_types=1);

use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Incident\Domain\IncidentReport\Livewire\IncidentForm;
use App\Modules\Incident\Domain\IncidentReport\Livewire\IncidentManager;
use App\Modules\Incident\Domain\IncidentReport\Models\IncidentReport;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

describe('3RU9S: incident Livewire components', function (): void {
    test('3RU9S-UC-INC-001, 3RU9S-FR-INC-004, 3RU9S-FR-INC-007: form saves a report for an active student registration and stamps reported_by', function (): void {
        $student = User::factory()->create();
        $student->assignRole('student');
        $registration = Registration::factory()->active()->create(['student_id' => $student->id]);
        $this->actingAs($student);

        Livewire::test(IncidentForm::class)
            ->set('formData', [
                'registration_id' => $registration->id,
                'incident_date' => '2026-09-14 10:30:00',
                'type' => 'accident',
                'severity' => 'high',
                'description' => 'A student slipped near the workshop entrance.',
                'location' => '',
                'action_taken' => '',
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('incident_reports', [
            'registration_id' => $registration->id,
            'reported_by' => $student->id,
            'type' => 'accident',
            'severity' => 'high',
        ]);
    });

    test('3RU9S-FR-INC-016: form rejects descriptions shorter than its filing minimum', function (): void {
        $student = User::factory()->create();
        $student->assignRole('student');
        $registration = Registration::factory()->active()->create(['student_id' => $student->id]);
        $this->actingAs($student);

        Livewire::test(IncidentForm::class)
            ->set('formData', [
                'registration_id' => $registration->id,
                'incident_date' => '2026-09-14 10:30:00',
                'type' => 'other',
                'severity' => 'low',
                'description' => 'Too short',
                'location' => '',
                'action_taken' => '',
            ])
            ->call('save')
            ->assertHasErrors(['formData.description']);

        expect(IncidentReport::count())->toBe(0);
    });

    test('3RU9S-UC-INC-005, 3RU9S-FR-INC-011: manager searches and filters incidents', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        IncidentReport::factory()->create(['description' => 'Broken guard in press room', 'severity' => 'critical']);
        IncidentReport::factory()->create(['description' => 'Routine concern', 'severity' => 'low']);

        $component = Livewire::test(IncidentManager::class)
            ->set('search', 'Broken guard')
            ->set('filters', ['severity' => 'critical']);
        $rows = $component->instance()->rows();

        expect($rows->total())->toBe(1)
            ->and($rows->first()->description)->toBe('Broken guard in press room');
    });

    test('3RU9S-UC-INC-003, 3RU9S-FR-INC-013: manager resolves an incident and closes its modal', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        $incident = IncidentReport::factory()->create(['status' => 'investigating']);

        Livewire::test(IncidentManager::class)
            ->call('resolve', $incident)
            ->set('resolveData.resolution_notes', 'The hazard was removed and documented.')
            ->call('saveResolve')
            ->assertSet('showResolveModal', false)
            ->assertSet('resolvingId', null);

        expect($incident->fresh()->status->value)->toBe('resolved')
            ->and($incident->fresh()->resolution_notes)->toBe('The hazard was removed and documented.');
    });
});
