<?php

declare(strict_types=1);

use App\Incident\IncidentReport\Livewire\IncidentManager;
use App\Incident\IncidentReport\Models\IncidentReport;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

describe('3RU9S: IncidentManager update wiring', function () {
    test('3RU9S-FR-I4: admin edits an incident via saveEdit with partial data', function () {
        actingAsAdmin();

        $incident = IncidentReport::factory()->create(['severity' => 'low']);

        Livewire::test(IncidentManager::class)
            ->call('edit', $incident->id)
            ->set('editData.severity', 'critical')
            ->set('editData.location', 'Workshop A')
            ->call('saveEdit');

        $fresh = $incident->fresh();

        expect($fresh->severity->value)->toBe('critical')
            ->and($fresh->location)->toBe('Workshop A')
            ->and($fresh->type->value)->toBe($incident->type->value);
    });

    test('3RU9S-FR-I4: saveEdit rejects invalid severity values', function () {
        actingAsAdmin();

        $incident = IncidentReport::factory()->create(['severity' => 'low']);

        Livewire::test(IncidentManager::class)
            ->call('edit', $incident->id)
            ->set('editData.severity', 'catastrophic')
            ->call('saveEdit')
            ->assertHasErrors('editData.severity');

        expect($incident->fresh()->severity->value)->toBe('low');
    });

    test('3RU9S-FR-I5: edit is policy-gated — non-admin cannot update', function () {
        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');
        $this->actingAs($supervisor);

        $incident = IncidentReport::factory()->create(['severity' => 'low']);

        Livewire::test(IncidentManager::class)
            ->call('edit', $incident->id)
            ->set('editData.severity', 'critical')
            ->call('saveEdit');

        expect($incident->fresh()->severity->value)->toBe('low');
    });
});
