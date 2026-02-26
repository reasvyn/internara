<?php

declare(strict_types=1);

namespace Modules\Internship\Tests\Feature\Livewire;

use Livewire\Livewire;
use Modules\Internship\Livewire\PlacementManager;
use Modules\Internship\Models\Company;
use Modules\Internship\Models\InternshipPlacement;
use Modules\User\Models\User;

beforeEach(function () {
    $role = \Modules\Permission\Models\Role::create(['name' => 'staff', 'guard_name' => 'web']);
    \Modules\Permission\Models\Permission::create(['name' => 'placement.view', 'guard_name' => 'web']);
    \Modules\Permission\Models\Permission::create(['name' => 'placement.manage', 'guard_name' => 'web']);
    \Modules\Permission\Models\Permission::create(['name' => 'internship.manage', 'guard_name' => 'web']);
    $role->givePermissionTo(['placement.view', 'placement.manage', 'internship.manage']);

    $this->user = User::factory()->create();
    $this->user->assignRole('staff');
    $this->actingAs($this->user);
});

test('placement management page is forbidden for unauthorized users', function () {
    $this->user->removeRole('staff');

    Livewire::test(PlacementManager::class)->assertForbidden();
});

test('placement management page is accessible by authorized users', function () {
    Livewire::test(PlacementManager::class)->assertOk();
});

test('it can create a new placement', function () {
    $internship = app(\Modules\Internship\Services\Contracts\InternshipService::class)
        ->factory()
        ->create();
    $company = Company::factory()->create(['name' => 'Google']);

    Livewire::test(PlacementManager::class)
        ->set('form.internship_id', $internship->id)
        ->set('form.company_id', $company->id)
        ->set('form.capacity_quota', 5)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('formModal', false);

    $this->assertDatabaseHas('internship_placements', [
        'company_id' => $company->id,
        'capacity_quota' => 5,
        'internship_id' => $internship->id,
    ]);
});

test('it can update an existing placement', function () {
    $company = Company::factory()->create(['name' => 'Old Company']);
    $newCompany = Company::factory()->create(['name' => 'New Company']);
    $placement = InternshipPlacement::factory()->create(['company_id' => $company->id]);

    Livewire::test(PlacementManager::class)
        ->call('edit', $placement->id)
        ->assertSet('form.company_id', $company->id)
        ->set('form.company_id', $newCompany->id)
        ->call('save')
        ->assertHasNoErrors();

    expect($placement->refresh()->company_id)->toBe($newCompany->id);
});

test('it can delete a placement', function () {
    $placement = InternshipPlacement::factory()->create();

    Livewire::test(PlacementManager::class)
        ->call('discard', $placement->id)
        ->assertSet('confirmModal', true)
        ->call('remove', $placement->id)
        ->assertSet('confirmModal', false);

    $this->assertDatabaseMissing('internship_placements', ['id' => $placement->id]);
});
