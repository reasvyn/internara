<?php

declare(strict_types=1);

namespace Modules\Internship\Tests\Feature\Livewire;

use Livewire\Livewire;
use Modules\Internship\Livewire\PlacementManager;
use Modules\Internship\Models\InternshipPlacement;
use Modules\User\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $role = \Modules\Permission\Models\Role::create(['name' => 'staff']);
    $permission = \Modules\Permission\Models\Permission::create(['name' => 'internship.update']);
    $role->givePermissionTo($permission);

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

    Livewire::test(PlacementManager::class)
        ->set('form.internship_id', $internship->id)
        ->set('form.company_name', 'Google')
        ->set('form.capacity_quota', 5)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('formModal', false);

    $this->assertDatabaseHas('internship_placements', [
        'company_name' => 'Google',
        'capacity_quota' => 5,
        'internship_id' => $internship->id,
    ]);
});

test('it can update an existing placement', function () {
    $placement = InternshipPlacement::factory()->create(['company_name' => 'Old Company']);

    Livewire::test(PlacementManager::class)
        ->call('edit', $placement->id)
        ->assertSet('form.company_name', 'Old Company')
        ->set('form.company_name', 'New Company')
        ->call('save')
        ->assertHasNoErrors();

    expect($placement->refresh()->company_name)->toBe('New Company');
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
