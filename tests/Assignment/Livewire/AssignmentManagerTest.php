<?php

declare(strict_types=1);

use App\Assignment\Livewire\AssignmentManager;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    test()->actingAs($admin);
});

test('renders', function () {
    Livewire::test(AssignmentManager::class)
        ->assertSuccessful();
});

test('opens create modal', function () {
    Livewire::test(AssignmentManager::class)
        ->call('create')
        ->assertSet('assignmentModal', true);
});

test('validates name is required', function () {
    Livewire::test(AssignmentManager::class)
        ->set('formData.title', '')
        ->set('formData.assignment_type', '')
        ->set('formData.internship_id', '')
        ->set('formData.due_date', '')
        ->call('save')
        ->assertHasErrors(['formData.title']);
});
