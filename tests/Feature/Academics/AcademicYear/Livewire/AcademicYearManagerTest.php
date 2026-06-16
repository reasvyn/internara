<?php

declare(strict_types=1);

use App\Academics\AcademicYear\Livewire\AcademicYearManager;
use App\Academics\AcademicYear\Models\AcademicYear;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    test()->actingAs($admin);
});

test('renders the academic year manager component', function () {
    Livewire::test(AcademicYearManager::class)
        ->assertSuccessful();
});

test('shows create modal', function () {
    Livewire::test(AcademicYearManager::class)
        ->call('create')
        ->assertSet('showModal', true);
});

test('opens edit modal with year data', function () {
    $year = AcademicYear::factory()->create();

    Livewire::test(AcademicYearManager::class)
        ->call('edit', $year->id)
        ->assertSet('showModal', true)
        ->assertSet('editingYearId', $year->id);
});

test('ask activate opens confirmation', function () {
    $year = AcademicYear::factory()->create(['is_active' => false]);

    Livewire::test(AcademicYearManager::class)
        ->call('askActivate', $year->id)
        ->assertSet('showConfirm', true)
        ->assertSet('confirmType', 'activate');
});

test('ask destroy opens confirmation', function () {
    $year = AcademicYear::factory()->create(['is_active' => false]);

    Livewire::test(AcademicYearManager::class)
        ->call('askDestroy', $year->id)
        ->assertSet('showConfirm', true)
        ->assertSet('confirmType', 'delete');
});
