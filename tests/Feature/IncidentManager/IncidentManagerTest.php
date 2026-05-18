<?php

declare(strict_types=1);

use App\Livewire\Incident\IncidentManager;
use App\Models\IncidentReport;
use App\Models\Registration;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

    $this->admin = User::factory()->create()->assignRole('admin');
    $this->actingAs($this->admin);

    $this->registration = Registration::factory()->create(['status' => 'active']);
});

it('lists reported incidents', function () {
    IncidentReport::create([
        'registration_id' => $this->registration->id,
        'reported_by' => $this->admin->id,
        'incident_date' => now(),
        'type' => 'accident',
        'severity' => 'high',
        'description' => 'Test incident description for listing.',
    ]);

    Livewire::test(IncidentManager::class)
        ->assertSuccessful()
        ->assertSee('Accident');
});

it('resolves an incident', function () {
    $incident = IncidentReport::create([
        'registration_id' => $this->registration->id,
        'reported_by' => $this->admin->id,
        'incident_date' => now(),
        'type' => 'accident',
        'severity' => 'low',
        'description' => 'Incident to resolve.',
    ]);

    Livewire::test(IncidentManager::class)
        ->call('resolve', $incident->id)
        ->set('resolveData.resolution_notes', 'Resolved after investigation.')
        ->set('resolveData.status', 'resolved')
        ->call('saveResolve')
        ->assertHasNoErrors();

    expect($incident->fresh()->status->value)->toBe('resolved');
});
