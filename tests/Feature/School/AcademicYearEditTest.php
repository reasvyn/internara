<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Domain\Auth\Enums\Role;
use App\Domain\School\Livewire\AcademicYearManager;
use App\Domain\School\Models\AcademicYear;
use App\Domain\User\Models\User;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    $this->user = User::factory()->create();
    RoleModel::firstOrCreate(['name' => Role::SUPER_ADMIN->value]);
    $this->user->assignRole(Role::SUPER_ADMIN->value);
    $this->actingAs($this->user);
});

it('opens modal with update button when editing', function () {
    $year = AcademicYear::factory()->create();

    Livewire::test(AcademicYearManager::class)
        ->call('edit', $year->id)
        ->assertSet('editingYearId', $year->id)
        ->assertSet('showModal', true);
});

it('updates academic year without unique validation error when name is unchanged', function () {
    $year = AcademicYear::factory()->create([
        'name' => '2025/2026',
    ]);

    Livewire::test(AcademicYearManager::class)
        ->call('edit', $year->id)
        ->call('update')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    expect($year->fresh()->name)->toBe('2025/2026');
});

it('updates academic year name to a new unique value', function () {
    $year = AcademicYear::factory()->create([
        'name' => '2025/2026',
    ]);

    Livewire::test(AcademicYearManager::class)
        ->call('edit', $year->id)
        ->set('form.name', '2026/2027')
        ->call('update')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    expect($year->fresh()->name)->toBe('2026/2027');
});

it('rejects duplicate name when updating', function () {
    AcademicYear::factory()->create(['name' => '2025/2026']);
    $year2 = AcademicYear::factory()->create(['name' => '2024/2025']);

    Livewire::test(AcademicYearManager::class)
        ->call('edit', $year2->id)
        ->set('form.name', '2025/2026')
        ->call('update')
        ->assertHasErrors('form.name');
});

it('creates new academic year without unique error', function () {
    Livewire::test(AcademicYearManager::class)
        ->call('create')
        ->assertSet('editingYearId', null)
        ->assertSet('showModal', true)
        ->set('form.name', '2026/2027')
        ->set('form.start_date', '2026-07-01')
        ->set('form.end_date', '2027-06-30')
        ->call('store')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    expect(AcademicYear::where('name', '2026/2027')->exists())->toBeTrue();
});
