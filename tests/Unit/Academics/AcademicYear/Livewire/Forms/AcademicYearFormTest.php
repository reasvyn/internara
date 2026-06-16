<?php

declare(strict_types=1);

use App\Academics\AcademicYear\Livewire\AcademicYearManager;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    test()->actingAs($admin);
});

test('AcademicYearForm renders within manager', function () {
    Livewire::test(AcademicYearManager::class)
        ->assertSuccessful();
});
