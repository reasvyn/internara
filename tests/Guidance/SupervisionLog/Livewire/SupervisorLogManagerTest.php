<?php

declare(strict_types=1);

use App\Guidance\SupervisionLog\Livewire\SupervisorLogManager;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $supervisor = User::factory()->create();
    $supervisor->assignRole('super_admin');
    test()->actingAs($supervisor);
});

test('renders the supervisor log manager component', function () {
    Livewire::test(SupervisorLogManager::class)
        ->assertSuccessful();
});

test('defaults type to guidance', function () {
    Livewire::test(SupervisorLogManager::class)
        ->assertSet('type', 'guidance');
});

test('shows create modal', function () {
    Livewire::test(SupervisorLogManager::class)
        ->call('create')
        ->assertSet('showModal', true);
});

test('validates registration_id is required on save', function () {
    Livewire::test(SupervisorLogManager::class)
        ->set('registrationId', '')
        ->set('date', now()->toDateString())
        ->set('topic', 'Test topic')
        ->set('notes', 'Test notes')
        ->call('save')
        ->assertHasErrors(['registrationId']);
});

test('validates date is required on save', function () {
    Livewire::test(SupervisorLogManager::class)
        ->set('registrationId', 'non-existent')
        ->set('date', '')
        ->set('topic', 'Test topic')
        ->set('notes', 'Test notes')
        ->call('save')
        ->assertHasErrors(['date']);
});

test('validates topic is required on save', function () {
    Livewire::test(SupervisorLogManager::class)
        ->set('registrationId', 'non-existent')
        ->set('date', now()->toDateString())
        ->set('topic', '')
        ->set('notes', 'Test notes')
        ->call('save')
        ->assertHasErrors(['topic']);
});

test('validates notes is required on save', function () {
    Livewire::test(SupervisorLogManager::class)
        ->set('registrationId', 'non-existent')
        ->set('date', now()->toDateString())
        ->set('topic', 'Test topic')
        ->set('notes', '')
        ->call('save')
        ->assertHasErrors(['notes']);
});

test('create resets form fields', function () {
    Livewire::test(SupervisorLogManager::class)
        ->set('registrationId', 'some-id')
        ->set('topic', 'Old topic')
        ->set('notes', 'Old notes')
        ->call('create')
        ->assertSet('registrationId', '')
        ->assertSet('topic', '')
        ->assertSet('notes', '');
});
