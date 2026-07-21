<?php

declare(strict_types=1);

use App\Document\Handbook\Livewire\HandbookManager;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    test()->actingAs($admin);
});

test('renders the handbook manager component', function () {
    Livewire::test(HandbookManager::class)
        ->assertSuccessful();
});

test('shows create modal', function () {
    Livewire::test(HandbookManager::class)
        ->call('create')
        ->assertSet('showModal', true)
        ->assertSet('uploadFile', null);
});

test('validates title is required', function () {
    Livewire::test(HandbookManager::class)
        ->set('form.title', '')
        ->set('form.audience', 'all')
        ->call('save')
        ->assertHasErrors(['form.title']);
});

test('validates audience is required', function () {
    Livewire::test(HandbookManager::class)
        ->set('form.title', 'Test Handbook')
        ->set('form.audience', '')
        ->call('save')
        ->assertHasErrors(['form.audience']);
});

test('validates audience must be a valid option', function () {
    Livewire::test(HandbookManager::class)
        ->set('form.title', 'Test Handbook')
        ->set('form.audience', 'invalid')
        ->call('save')
        ->assertHasErrors(['form.audience']);
});

test('validates upload file is required for new handbooks', function () {
    Livewire::test(HandbookManager::class)
        ->set('form.title', 'Test Handbook')
        ->set('form.audience', 'all')
        ->call('save')
        ->assertHasErrors(['uploadFile']);
});

test('ask delete opens confirmation', function () {
    Livewire::test(HandbookManager::class)
        ->call('askDelete', 'some-id')
        ->assertSet('showConfirm', true)
        ->assertSet('confirmType', 'delete');
});

test('provides audience options', function () {
    Livewire::test(HandbookManager::class)
        ->assertSet('audienceOptions', fn ($options) => count($options) > 0);
});
