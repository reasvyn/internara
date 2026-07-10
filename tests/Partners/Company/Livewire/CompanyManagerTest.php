<?php

declare(strict_types=1);

use App\Partners\Company\Livewire\CompanyManager;
use App\Partners\Company\Models\Company;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    test()->actingAs($admin);
});

test('renders the company manager component', function () {
    Livewire::test(CompanyManager::class)
        ->assertSuccessful();
});

test('shows create modal', function () {
    Livewire::test(CompanyManager::class)
        ->call('create')
        ->assertSet('showModal', true);
});

test('opens edit modal with company data', function () {
    $company = Company::factory()->create();

    Livewire::test(CompanyManager::class)
        ->call('edit', $company->id)
        ->assertSet('showModal', true)
        ->assertSet('form.name', $company->name);
});

test('ask delete opens confirmation', function () {
    $company = Company::factory()->create();

    Livewire::test(CompanyManager::class)
        ->call('askDelete', $company->id)
        ->assertSet('showConfirm', true)
        ->assertSet('confirmType', 'delete');
});

test('creates a new company', function () {
    Livewire::test(CompanyManager::class)
        ->set('form.name', 'PT Baru')
        ->set('form.address', 'Jakarta')
        ->call('save')
        ->assertSet('showModal', false);

    expect(Company::where('name', 'PT Baru')->exists())->toBeTrue();
});

test('edit opens modal with company data', function () {
    $company = Company::factory()->create(['name' => 'Old Name']);

    Livewire::test(CompanyManager::class)
        ->call('edit', $company->id)
        ->assertSet('showModal', true)
        ->assertSet('form.name', 'Old Name');
});
