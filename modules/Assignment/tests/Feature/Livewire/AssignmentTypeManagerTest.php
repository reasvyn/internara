<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Assignment\Database\Seeders\AssignmentSeeder;
use Modules\Assignment\Livewire\AssignmentTypeManager;
use Modules\Assignment\Models\AssignmentType;
use Modules\Permission\Models\Role;
use Modules\User\Models\User;


beforeEach(function () {
    $this->seed(AssignmentSeeder::class);

    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->actingAs($this->admin);
});

test('admin can see assignment types list', function () {
    Livewire::test(AssignmentTypeManager::class)->assertOk()->assertSee('Laporan Kegiatan PKL');
});

test('admin can create a new assignment type', function () {
    Livewire::test(AssignmentTypeManager::class)
        ->set('name', 'Final Report')
        ->set('slug', 'final-report')
        ->set('group', 'report')
        ->call('save')
        ->assertHasNoErrors();

    expect(AssignmentType::where('slug', 'final-report')->exists())->toBeTrue();
});

test('admin can delete an assignment type', function () {
    $type = AssignmentType::factory()->create(['name' => 'To be deleted']);

    Livewire::test(AssignmentTypeManager::class)->call('remove', $type->id)->assertOk();

    expect(AssignmentType::where('id', $type->id)->exists())->toBeFalse();
});
