<?php

declare(strict_types=1);

use App\Program\Internship\Livewire\InternshipManager;
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
    Livewire::test(InternshipManager::class)
        ->assertSuccessful();
});

test('opens create modal', function () {
    Livewire::test(InternshipManager::class)
        ->call('create')
        ->assertSet('showModal', true);
});
