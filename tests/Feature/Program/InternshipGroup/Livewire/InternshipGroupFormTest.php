<?php

declare(strict_types=1);

use App\Program\InternshipGroup\Livewire\InternshipGroupManager;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    test()->actingAs($admin);
});

test('renders within group manager', function () {
    Livewire::test(InternshipGroupManager::class)
        ->assertSuccessful();
});

test('validates name is required via form', function () {
    Livewire::test(InternshipGroupManager::class)
        ->call('create')
        ->set('form.name', '')
        ->call('save')
        ->assertHasErrors(['form.name']);
});
