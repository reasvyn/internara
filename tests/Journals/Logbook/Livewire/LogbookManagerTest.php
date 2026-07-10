<?php

declare(strict_types=1);

use App\Journals\Logbook\Livewire\LogbookManager;
use App\Journals\Logbook\Models\Logbook;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    test()->actingAs($admin);
});

test('renders the logbook manager component', function () {
    Livewire::test(LogbookManager::class)
        ->assertSuccessful();
});

test('shows create modal', function () {
    Livewire::test(LogbookManager::class)
        ->call('create')
        ->assertSet('showModal', true);
});

test('opens edit modal with logbook data', function () {
    $student = User::factory()->create();
    $logbook = Logbook::factory()->create(['user_id' => $student->id]);

    Livewire::test(LogbookManager::class)
        ->call('edit', $logbook->id)
        ->assertSet('showModal', true)
        ->assertSet('form.id', $logbook->id);
});

test('ask delete opens confirmation', function () {
    $student = User::factory()->create();
    $logbook = Logbook::factory()->create(['user_id' => $student->id]);

    Livewire::test(LogbookManager::class)
        ->call('askDelete', $logbook->id)
        ->assertSet('showConfirm', true);
});

test('validates form fields on save', function () {
    Livewire::test(LogbookManager::class)
        ->set('form.date', '')
        ->set('form.content', '')
        ->set('form.status', '')
        ->call('save')
        ->assertHasErrors(['form.date', 'form.content', 'form.status']);
});

test('opens supervisor note modal', function () {
    $student = User::factory()->create();
    $logbook = Logbook::factory()->create(['user_id' => $student->id]);

    Livewire::test(LogbookManager::class)
        ->call('editSupervisorNote', $logbook->id)
        ->assertSet('showSupervisorNoteModal', true)
        ->assertSet('supervisorNoteEntryId', $logbook->id);
});
