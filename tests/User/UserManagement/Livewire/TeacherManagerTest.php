<?php

declare(strict_types=1);

use App\User\UserManagement\Livewire\TeacherManager;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    test()->actingAs($admin);
});

test('renders teacher manager', function () {
    Livewire::test(TeacherManager::class)
        ->assertSuccessful();
});

test('opens create teacher modal', function () {
    Livewire::test(TeacherManager::class)
        ->call('create')
        ->assertSet('userModal', true);
});

test('lists teacher users', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('teacher');

    Livewire::test(TeacherManager::class)
        ->assertSee($teacher->name);
});
