<?php

declare(strict_types=1);

use App\User\Dashboard\Livewire\AdminDashboard;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    test()->actingAs($admin);
});

test('renders admin dashboard', function () {
    Livewire::test(AdminDashboard::class)
        ->assertSuccessful();
});
