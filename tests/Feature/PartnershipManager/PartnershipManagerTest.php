<?php

declare(strict_types=1);

use App\Livewire\Internship\PartnershipManager;
use App\Models\Company;
use App\Models\Partnership;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

    $this->admin = User::factory()->create()->assignRole('admin');
    $this->actingAs($this->admin);

    $this->company = Company::factory()->create(['name' => 'Tech Corp']);
});

it('shows partnership listing page', function () {
    Partnership::create([
        'company_id' => $this->company->id,
        'agreement_number' => '001/PKS/2025',
        'title' => 'PKL Partnership 2025',
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
    ]);

    Livewire::test(PartnershipManager::class)
        ->assertSuccessful()
        ->assertSee('001/PKS/2025')
        ->assertSee('Tech Corp');
});

it('creates a new partnership', function () {
    Livewire::test(PartnershipManager::class)
        ->call('create')
        ->set('formData.company_id', $this->company->id)
        ->set('formData.agreement_number', '002/PKS/2025')
        ->set('formData.title', 'PKL Test')
        ->set('formData.start_date', '2025-06-01')
        ->set('formData.end_date', '2026-05-31')
        ->call('save')
        ->assertHasNoErrors();

    expect(Partnership::where('agreement_number', '002/PKS/2025')->exists())->toBeTrue();
});

it('terminates an active partnership', function () {
    $partnership = Partnership::create([
        'company_id' => $this->company->id,
        'agreement_number' => '003/PKS/2025',
        'title' => 'To Terminate',
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
    ]);

    Livewire::test(PartnershipManager::class)
        ->call('terminate', $partnership->id)
        ->assertSuccessful();

    expect($partnership->fresh()->status->value)->toBe('terminated');
});
