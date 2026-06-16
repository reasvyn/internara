<?php

declare(strict_types=1);

use App\Academics\Department\Livewire\DepartmentManager;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    test()->actingAs($admin);
});

test('DepartmentForm renders within manager', function () {
    Livewire::test(DepartmentManager::class)
        ->assertSuccessful();
});
