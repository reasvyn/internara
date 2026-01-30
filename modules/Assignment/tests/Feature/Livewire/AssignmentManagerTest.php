<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Assignment\Database\Seeders\AssignmentSeeder;
use Modules\Assignment\Livewire\AssignmentManager;
use Modules\Assignment\Models\Assignment;
use Modules\Assignment\Models\AssignmentType;
use Modules\Permission\Models\Role;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(AssignmentSeeder::class);

    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->actingAs($this->admin);
});

test('admin can see assignment management page', function () {
    Livewire::test(AssignmentManager::class)
        ->assertOk()
        ->assertSee(__('assignment::ui.add_assignment'));
});

test('admin can create a new assignment', function () {
    $type = AssignmentType::first();

    Livewire::test(AssignmentManager::class)
        ->set('title', 'Sertifikat Industri')
        ->set('assignment_type_id', $type->id)
        ->set('is_mandatory', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('notify', message: __('assignment::ui.success_created'));

    $this->assertDatabaseHas('assignments', [
        'title' => 'Sertifikat Industri',
        'is_mandatory' => true,
    ]);
});

test('admin can delete an assignment', function () {
    $assignment = Assignment::factory()->create(['title' => 'To be deleted']);

    Livewire::test(AssignmentManager::class)
        ->call('remove', $assignment->id)
        ->assertDispatched('notify', message: __('assignment::ui.success_deleted'));

    $this->assertDatabaseMissing('assignments', ['id' => $assignment->id]);
});
