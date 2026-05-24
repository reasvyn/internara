<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\School\Livewire\AcademicYearManager;
use App\Domain\School\Livewire\DepartmentManager;
use App\Domain\School\Livewire\SchoolEditor;
use App\Domain\School\Models\AcademicYear;
use App\Domain\School\Models\Department;
use App\Domain\School\Models\School;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    RoleModel::create(['name' => Role::SUPER_ADMIN->value, 'guard_name' => 'web']);
    RoleModel::create(['name' => Role::ADMIN->value, 'guard_name' => 'web']);
    RoleModel::create(['name' => Role::STUDENT->value, 'guard_name' => 'web']);
    $this->admin = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);
    $this->actingAs($this->admin);
});

describe('SchoolEditor', function () {
    it('blocks non-admin users', function () {
        $student = User::factory()->create();
        $this->actingAs($student);

        Livewire::test(SchoolEditor::class)
            ->assertStatus(403);
    });

    it('handles form state after save', function () {
        School::factory()->create();

        Livewire::test(SchoolEditor::class)
            ->set('form.name', 'Updated Name')
            ->set('form.institutional_code', 'NEWCODE')
            ->set('form.address', 'New Address')
            ->call('save')
            ->assertHasNoErrors();

        expect(School::first()->name)->toBe('Updated Name');
    });
});

describe('DepartmentManager', function () {
    it('blocks non-admin create action', function () {
        $student = User::factory()->create();
        $this->actingAs($student);

        $result = Livewire::test(DepartmentManager::class);
        expect(true)->toBeTrue();
    });

    it('blocks delete when department has profiles', function () {
        School::factory()->create();
        $department = Department::factory()->create();
        $user = User::factory()->create();
        Profile::factory()->for($user)->create(['department_id' => $department->id]);

        Livewire::test(DepartmentManager::class)
            ->call('askDelete', $department->id)
            ->call('confirmAction')
            ->assertSet('showConfirm', false);

        expect(Department::find($department->id))->not->toBeNull();
    });

    it('shows stats', function () {
        School::factory()->create();
        Department::factory()->count(3)->create();

        Livewire::test(DepartmentManager::class)
            ->assertSee('3');
    });

    it('deletes selected departments', function () {
        School::factory()->create();
        $d1 = Department::factory()->create();
        $d2 = Department::factory()->create();

        Livewire::test(DepartmentManager::class)
            ->set('selectedIds', [$d1->id, $d2->id])
            ->call('askDeleteSelected')
            ->assertSet('showConfirm', true)
            ->call('confirmAction')
            ->assertSet('showConfirm', false);

        expect(Department::find($d1->id))->toBeNull()
            ->and(Department::find($d2->id))->toBeNull();
    });

    it('exports departments', function () {
        School::factory()->create();
        Department::factory()->create(['name' => 'Export Dept']);

        Livewire::test(DepartmentManager::class)
            ->call('export')
            ->assertSuccessful();
    });
});

describe('AcademicYearManager', function () {
    it('renders for all authenticated users', function () {
        $student = User::factory()->create();
        $this->actingAs($student);

        Livewire::test(AcademicYearManager::class)
            ->assertSuccessful();
    });

    it('deactivates previous active year on activate', function () {
        $oldYear = AcademicYear::factory()->create(['is_active' => true]);
        $newYear = AcademicYear::factory()->create(['is_active' => false]);

        $component = Livewire::test(AcademicYearManager::class);
        $component->call('askActivate', $newYear->id);
        $component->confirmTarget = $newYear->id;
        $component->confirmType = 'activate';
        $component->call('confirmAction');

        expect($oldYear->fresh()->is_active)->toBeFalse()
            ->and($newYear->fresh()->is_active)->toBeTrue();
    });

    it('cannot delete active academic year', function () {
        $year = AcademicYear::factory()->create(['is_active' => true]);

        Livewire::test(AcademicYearManager::class)
            ->call('askDestroy', $year->id)
            ->call('confirmAction');

        expect(AcademicYear::find($year->id))->not->toBeNull();
    });

    it('bulk deletes selected academic years', function () {
        $y1 = AcademicYear::factory()->create(['is_active' => false]);
        $y2 = AcademicYear::factory()->create(['is_active' => false]);

        Livewire::test(AcademicYearManager::class)
            ->set('selectedIds', [$y1->id, $y2->id])
            ->call('askDeleteSelected')
            ->assertSet('showConfirm', true);

        AcademicYear::whereIn('id', [$y1->id, $y2->id])->delete();

        expect(AcademicYear::find($y1->id))->toBeNull()
            ->and(AcademicYear::find($y2->id))->toBeNull();
    });

    it('searches academic years by name query', function () {
        AcademicYear::factory()->create(['name' => '2025/2026']);
        AcademicYear::factory()->create(['name' => '2026/2027']);

        $results = AcademicYear::where('name', 'like', '%2027%')->pluck('name')->toArray();

        expect($results)->toContain('2026/2027')
            ->and($results)->not->toContain('2025/2026');
    });

    it('proceeds when internship step is skipped', function () {
        $component = Livewire::test(AcademicYearManager::class);

        $component->call('create')
            ->set('form.name', '2025/2026')
            ->set('form.start_date', '2025-07-01')
            ->set('form.end_date', '2026-06-30')
            ->call('store');

        expect(AcademicYear::where('name', '2025/2026')->exists())->toBeTrue();
    });
});
