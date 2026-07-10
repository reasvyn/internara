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

test('renders within teacher manager', function () {
    Livewire::test(TeacherManager::class)
        ->assertSuccessful();
});

test('validates name is required via form', function () {
    Livewire::test(TeacherManager::class)
        ->call('create')
        ->set('form.name', '')
        ->set('form.email', '')
        ->call('save')
        ->assertHasErrors(['form.name']);
});

test('validates email is required via form', function () {
    Livewire::test(TeacherManager::class)
        ->call('create')
        ->set('form.name', 'Test Teacher')
        ->set('form.email', '')
        ->call('save')
        ->assertHasErrors(['form.email']);
});
