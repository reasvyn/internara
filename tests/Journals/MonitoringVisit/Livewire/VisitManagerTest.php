<?php

declare(strict_types=1);

use App\Journals\MonitoringVisit\Livewire\VisitManager;
use App\Journals\MonitoringVisit\Models\MonitoringVisit;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    test()->actingAs($admin);
});

test('renders the visit manager component', function () {
    Livewire::test(VisitManager::class)
        ->assertSuccessful();
});

test('shows create modal', function () {
    Livewire::test(VisitManager::class)
        ->call('create')
        ->assertSet('showModal', true)
        ->assertSet('visitDate', now()->toDateString());
});

test('validates registration_id is required', function () {
    Livewire::test(VisitManager::class)
        ->set('registrationId', '')
        ->set('visitDate', now()->toDateString())
        ->set('method', 'site_visit')
        ->call('save')
        ->assertHasErrors(['registrationId']);
});

test('validates visit_date is required', function () {
    Livewire::test(VisitManager::class)
        ->set('registrationId', 'non-existent')
        ->set('visitDate', '')
        ->set('method', 'site_visit')
        ->call('save')
        ->assertHasErrors(['visitDate']);
});

test('validates method is required', function () {
    Livewire::test(VisitManager::class)
        ->set('registrationId', 'non-existent')
        ->set('visitDate', now()->toDateString())
        ->set('method', '')
        ->call('save')
        ->assertHasErrors(['method']);
});

test('validates method must be a valid option', function () {
    Livewire::test(VisitManager::class)
        ->set('registrationId', 'non-existent')
        ->set('visitDate', now()->toDateString())
        ->set('method', 'invalid_method')
        ->call('save')
        ->assertHasErrors(['method']);
});

test('ask verify opens confirmation', function () {
    $visit = MonitoringVisit::factory()->create(['teacher_id' => auth()->id()]);

    Livewire::test(VisitManager::class)
        ->call('askVerify', $visit->id)
        ->assertSet('showConfirm', true)
        ->assertSet('confirmType', 'verify');
});

test('provides method options', function () {
    Livewire::test(VisitManager::class)
        ->assertSet('methodOptions', fn ($options) => count($options) > 0);
});
