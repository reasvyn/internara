<?php

declare(strict_types=1);

use App\Partners\Company\Models\Company;
use App\Partners\Partnership\Livewire\PartnershipManager;
use App\Partners\Partnership\Models\Partnership;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    test()->actingAs($admin);
});

test('renders the partnership manager component', function () {
    Livewire::test(PartnershipManager::class)
        ->assertSuccessful();
});

test('shows create modal', function () {
    Livewire::test(PartnershipManager::class)
        ->call('create')
        ->assertSet('showModal', true);
});

test('opens edit modal with partnership data', function () {
    $company = Company::factory()->create();
    $partnership = Partnership::factory()->create(['company_id' => $company->id]);

    Livewire::test(PartnershipManager::class)
        ->call('edit', $partnership->id)
        ->assertSet('showModal', true)
        ->assertSet('form.agreement_number', $partnership->agreement_number);
});

test('ask delete opens confirmation', function () {
    $company = Company::factory()->create();
    $partnership = Partnership::factory()->create(['company_id' => $company->id]);

    Livewire::test(PartnershipManager::class)
        ->call('askDelete', $partnership->id)
        ->assertSet('showConfirm', true)
        ->assertSet('confirmType', 'delete');
});

test('creates a new partnership', function () {
    $company = Company::factory()->create();

    Livewire::test(PartnershipManager::class)
        ->set('form.company_id', $company->id)
        ->set('form.agreement_number', 'MOU-001/2026')
        ->set('form.title', 'Test Partnership')
        ->set('form.start_date', now()->toDateString())
        ->set('form.end_date', now()->addYear()->toDateString())
        ->call('save')
        ->assertSet('showModal', false);

    expect(Partnership::where('agreement_number', 'MOU-001/2026')->exists())->toBeTrue();
});

test('terminates an active partnership', function () {
    $company = Company::factory()->create();
    $partnership = Partnership::factory()->create([
        'company_id' => $company->id,
        'title' => 'Test',
        'status' => 'active',
    ]);

    Livewire::test(PartnershipManager::class)
        ->call('terminate', $partnership->id)
        ->assertStatus(200);

    expect($partnership->fresh()->status->value)->toBe('terminated');
});
