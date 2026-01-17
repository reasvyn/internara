<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Internship\Livewire\PlacementManager;
use Modules\Internship\Models\Internship;
use Modules\Internship\Models\InternshipPlacement;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Modules\Permission\Database\Seeders\PermissionDatabaseSeeder::class);
});

test('placement management page is forbidden for unauthorized users', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('internship.placement.index'))->assertForbidden();
});

test('placement management page is accessible by authorized users', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('internship.update');
    $this->actingAs($user);

    $this->get(route('internship.placement.index'))->assertOk()->assertSee('Penempatan');
});

test('it can create a new placement', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['internship.update']);
    $this->actingAs($user);

    $internship = Internship::factory()->create();

    Livewire::test(PlacementManager::class)
        ->call('add')
        ->set('form.internship_id', $internship->id)
        ->set('form.company_name', 'Google')
        ->set('form.slots', 5)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('formModal', false);

    $this->assertDatabaseHas('internship_placements', [
        'company_name' => 'Google',
        'slots' => 5,
        'internship_id' => $internship->id,
    ]);
});

test('it can update an existing placement', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['internship.update']);
    $this->actingAs($user);

    $placement = InternshipPlacement::factory()->create(['company_name' => 'Old Company']);

    Livewire::test(PlacementManager::class)
        ->call('edit', $placement->id)
        ->set('form.company_name', 'New Company')
        ->call('save')
        ->assertHasNoErrors();

    expect($placement->refresh()->company_name)->toBe('New Company');
});

test('it can delete a placement', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['internship.update']);
    $this->actingAs($user);

    $placement = InternshipPlacement::factory()->create();

    Livewire::test(PlacementManager::class)
        ->call('discard', $placement->id)
        ->call('remove', $placement->id)
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('internship_placements', ['id' => $placement->id]);
});
