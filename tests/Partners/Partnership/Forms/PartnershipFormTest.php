<?php

declare(strict_types=1);

use App\Partners\Company\Models\Company;
use App\Partners\Partnership\Livewire\PartnershipManager;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    test()->actingAs($admin);
});

test('partnership form renders within manager', function () {
    Livewire::test(PartnershipManager::class)
        ->assertSuccessful();
});

test('partnership form sets default values on create', function () {
    Livewire::test(PartnershipManager::class)
        ->call('create')
        ->assertSet('form.company_id', '')
        ->assertSet('form.agreement_number', '');
});

test('partnership form sets values correctly', function () {
    $company = Company::factory()->create();

    Livewire::test(PartnershipManager::class)
        ->call('create')
        ->set('form.company_id', $company->id)
        ->set('form.agreement_number', 'MOU-001')
        ->assertSet('form.company_id', $company->id)
        ->assertSet('form.agreement_number', 'MOU-001');
});
