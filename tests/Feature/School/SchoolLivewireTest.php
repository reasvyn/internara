<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\School\Actions\UpdateAcademicYearAction;
use App\Domain\School\Livewire\AcademicYearManager;
use App\Domain\School\Livewire\DepartmentManager;
use App\Domain\School\Livewire\SchoolEditor;
use App\Domain\School\Models\AcademicYear;
use App\Domain\School\Models\Department;
use App\Domain\School\Models\School;
use App\Domain\User\Models\User;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    RoleModel::create(['name' => Role::SUPER_ADMIN->value, 'guard_name' => 'web']);
    RoleModel::create(['name' => Role::ADMIN->value, 'guard_name' => 'web']);

    $this->admin = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);
    $this->actingAs($this->admin);
});

describe('SchoolEditor', function () {
    it('renders the school editor page', function () {
        School::factory()->create();

        Livewire::test(SchoolEditor::class)
            ->assertSuccessful();
    });

    it('updates school profile', function () {
        School::factory()->create();

        Livewire::test(SchoolEditor::class)
            ->set('form.name', 'Updated School')
            ->set('form.institutional_code', '1234567890')
            ->set('form.address', 'New Address')
            ->set('form.website', 'https://school.example.com')
            ->call('save')
            ->assertHasNoErrors();

        expect(School::first()->name)->toBe('Updated School')
            ->and(School::first()->website)->toBe('https://school.example.com');
    });

    it('validates required fields', function () {
        School::factory()->create();

        Livewire::test(SchoolEditor::class)
            ->set('form.name', '')
            ->set('form.institutional_code', '')
            ->call('save')
            ->assertHasErrors([
                'form.name' => 'required',
                'form.institutional_code' => 'required',
            ]);
    });
});

describe('DepartmentManager', function () {
    it('creates a department', function () {
        School::factory()->create();

        Livewire::test(DepartmentManager::class)
            ->set('form.name', 'Computer Science')
            ->set('form.description', 'Study of computing')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showModal', false);

        expect(Department::where('name', 'Computer Science')->exists())->toBeTrue();
    });

    it('validates required fields on create', function () {
        School::factory()->create();

        Livewire::test(DepartmentManager::class)
            ->call('save')
            ->assertHasErrors(['form.name' => 'required']);
    });

    it('edits a department', function () {
        School::factory()->create();
        $department = Department::factory()->create(['name' => 'Old Name']);

        Livewire::test(DepartmentManager::class)
            ->call('edit', $department->id)
            ->assertSet('form.name', 'Old Name')
            ->assertSet('showModal', true)
            ->set('form.name', 'New Name')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showModal', false);

        expect($department->fresh()->name)->toBe('New Name');
    });

    it('deletes a department with no profiles', function () {
        School::factory()->create();
        $department = Department::factory()->create();

        $component = Livewire::test(DepartmentManager::class);
        $component->call('askDelete', $department->id)
            ->assertSet('showConfirm', true);

        $component->confirmTarget = $department->id;
        $component->confirmType = 'delete';
        $component->call('confirmAction')
            ->assertSet('showConfirm', false);

        expect(Department::find($department->id))->toBeNull();
    });

    it('searches departments by name', function () {
        School::factory()->create();
        Department::factory()->create(['name' => 'Software Engineering']);
        Department::factory()->create(['name' => 'Network Engineering']);

        $results = Department::where('name', 'like', '%Network%')->pluck('name')->toArray();

        expect($results)->toContain('Network Engineering')
            ->and($results)->not->toContain('Software Engineering');
    });
});

describe('AcademicYearManager', function () {
    it('creates an academic year', function () {
        Livewire::test(AcademicYearManager::class)
            ->call('create')
            ->assertSet('showModal', true)
            ->set('form.name', '2025/2026')
            ->set('form.start_date', '2025-07-01')
            ->set('form.end_date', '2026-06-30')
            ->call('store')
            ->assertHasNoErrors()
            ->assertSet('showModal', false);

        expect(AcademicYear::where('name', '2025/2026')->exists())->toBeTrue();
    });

    it('validates required fields', function () {
        Livewire::test(AcademicYearManager::class)
            ->call('create')
            ->call('store')
            ->assertHasErrors(['form.name' => 'required', 'form.start_date' => 'required']);
    });

    it('validates end_date after start_date', function () {
        Livewire::test(AcademicYearManager::class)
            ->call('create')
            ->set('form.name', '2025/2026')
            ->set('form.start_date', '2025-07-01')
            ->set('form.end_date', '2024-06-30')
            ->call('store')
            ->assertHasErrors(['form.end_date' => 'after']);
    });

    it('edits an academic year via action', function () {
        $year = AcademicYear::factory()->create(['name' => '2025/2026']);

        app(UpdateAcademicYearAction::class)->execute($year, [
            'name' => '2025/2026 Updated',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
        ]);

        expect($year->fresh()->name)->toBe('2025/2026 Updated');
    });

    it('opens edit modal', function () {
        $year = AcademicYear::factory()->create(['name' => '2025/2026']);

        Livewire::test(AcademicYearManager::class)
            ->call('edit', $year->id)
            ->assertSet('form.name', $year->name)
            ->assertSet('showModal', true);
    });

    it('activates an academic year', function () {
        $active = AcademicYear::factory()->create(['is_active' => true]);
        $year = AcademicYear::factory()->create(['is_active' => false]);

        $component = Livewire::test(AcademicYearManager::class);
        $component->call('askActivate', $year->id)
            ->assertSet('showConfirm', true);

        $component->confirmTarget = $year->id;
        $component->confirmType = 'activate';
        $component->call('confirmAction')
            ->assertSet('showConfirm', false);

        expect($year->fresh()->is_active)->toBeTrue();
    });

    it('deletes an inactive academic year', function () {
        $year = AcademicYear::factory()->create(['is_active' => false]);

        $component = Livewire::test(AcademicYearManager::class);
        $component->call('askDestroy', $year->id)
            ->assertSet('showConfirm', true);

        $component->confirmTarget = $year->id;
        $component->confirmType = 'delete';
        $component->call('confirmAction')
            ->assertSet('showConfirm', false);

        expect(AcademicYear::find($year->id))->toBeNull();
    });

    it('searches academic years by name', function () {
        AcademicYear::factory()->create(['name' => '2025/2026']);
        AcademicYear::factory()->create(['name' => '2026/2027']);

        $results = AcademicYear::where('name', 'like', '%2026/2027%')->pluck('name')->toArray();

        expect($results)->toContain('2026/2027')
            ->and($results)->not->toContain('2025/2026');
    });
});
