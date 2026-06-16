<?php

declare(strict_types=1);

use App\Partners\Company\Livewire\CompanyManager;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    test()->actingAs($admin);
});

test('company form renders within manager', function () {
    Livewire::test(CompanyManager::class)
        ->assertSuccessful();
});

test('company form sets default values on create', function () {
    Livewire::test(CompanyManager::class)
        ->call('create')
        ->assertSet('form.name', '')
        ->assertSet('form.address', '');
});

test('company form toArray contains expected keys', function () {
    Livewire::test(CompanyManager::class)
        ->call('create')
        ->set('form.name', 'Test Company')
        ->set('form.address', 'Jakarta')
        ->assertSet('form.name', 'Test Company')
        ->assertSet('form.address', 'Jakarta');
});
