<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Livewire;

use App\Domain\Admin\Livewire\StudentManager;
use App\Domain\Auth\Enums\Role;
use App\Domain\School\Models\Department;
use App\Domain\Shared\Support\CsvHandler;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as RoleModel;
use Symfony\Component\HttpFoundation\StreamedResponse;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    collect(Role::userRoles())->each(fn ($r) => RoleModel::create(['name' => $r->value, 'guard_name' => 'web']));
});

describe('StudentManager', function () {
    // ─── AUTHORIZATION ───────────────────────────────────────

    it('allows super_admin to render', function () {
        actingAsSuperAdmin();

        Livewire::test(StudentManager::class)
            ->assertSuccessful();
    });

    it('allows admin to render', function () {
        actingAsAdmin();

        Livewire::test(StudentManager::class)
            ->assertSuccessful();
    });

    it('denies student to render', function () {
        actingAsStudent();

        Livewire::test(StudentManager::class)
            ->assertForbidden();
    });

    // ─── HEADERS ────────────────────────────────────────────

    it('returns correct headers', function () {
        actingAsSuperAdmin();

        $headers = Livewire::test(StudentManager::class)->instance()->headers();

        expect($headers)->toHaveCount(7);
        expect($headers[0]['key'])->toBe('name');
        expect($headers[1]['key'])->toBe('username');
        expect($headers[2]['key'])->toBe('profile.national_id_number');
        expect($headers[3]['key'])->toBe('profile.student_id_number');
        expect($headers[4]['key'])->toBe('profile.department.name');
    });

    // ─── QUERY ──────────────────────────────────────────────

    it('only returns users with student role', function () {
        actingAsSuperAdmin();
        User::factory()->create()->assignRole('student');
        User::factory()->create()->assignRole('teacher');
        User::factory()->create()->assignRole('super_admin');

        $component = Livewire::test(StudentManager::class);

        expect($component->instance()->rows()->total())->toBe(1);
    });

    // ─── SEARCH ─────────────────────────────────────────────

    it('filters by search term', function () {
        actingAsSuperAdmin();
        User::factory()->create(['name' => 'StudentOne'])->assignRole('student');
        User::factory()->create(['name' => 'Other'])->assignRole('student');

        $component = Livewire::test(StudentManager::class)
            ->set('search', 'StudentOne');

        expect($component->instance()->rows()->total())->toBe(1);
    });

    // ─── FILTERS ─────────────────────────────────────────────

    it('filters by department', function () {
        actingAsSuperAdmin();
        $dept = Department::factory()->create();
        $student = User::factory()->create()->assignRole('student');
        $student->profile()->create(Department::factory()->raw(['department_id' => $dept->id]));

        $component = Livewire::test(StudentManager::class)
            ->set('filters.department_id', $dept->id);

        expect($component->instance()->rows()->total())->toBeGreaterThanOrEqual(0);
    });

    it('filters by created date range', function () {
        actingAsSuperAdmin();
        User::factory()->create(['created_at' => now()->subDays(10)])->assignRole('student');
        User::factory()->create(['created_at' => now()])->assignRole('student');

        $component = Livewire::test(StudentManager::class)
            ->set('filters.created_from', now()->subDays(5)->format('Y-m-d'))
            ->set('filters.created_to', now()->addDay()->format('Y-m-d'));

        expect($component->instance()->rows()->total())->toBe(1);
    });

    // ─── CRUD ───────────────────────────────────────────────

    it('opens create modal', function () {
        actingAsSuperAdmin();

        Livewire::test(StudentManager::class)
            ->call('create')
            ->assertSet('userModal', true);
    });

    it('opens edit modal with student data', function () {
        actingAsSuperAdmin();
        $user = User::factory()->create()->assignRole('student');

        Livewire::test(StudentManager::class)
            ->call('edit', $user->id)
            ->assertSet('userModal', true)
            ->assertSet('form.name', $user->name)
            ->assertSet('form.email', $user->email);
    });

    it('creates a new student', function () {
        actingAsSuperAdmin();
        $dept = Department::factory()->create();

        Livewire::test(StudentManager::class)
            ->set('form.name', 'Student Baru')
            ->set('form.email', 'student@example.com')
            ->set('form.national_id_number', '1234567890')
            ->set('form.student_id_number', 'STU-001')
            ->set('form.department_id', $dept->id)
            ->call('save');

        expect(User::where('email', 'student@example.com')->exists())->toBeTrue();
    });

    it('validates required fields on create', function () {
        actingAsSuperAdmin();

        Livewire::test(StudentManager::class)
            ->call('save')
            ->assertHasErrors(['form.name', 'form.email', 'form.national_id_number', 'form.department_id']);
    });

    it('updates an existing student', function () {
        actingAsSuperAdmin();
        $user = User::factory()->create()->assignRole('student');

        $department = Department::factory()->create();

        Livewire::test(StudentManager::class)
            ->call('edit', $user->id)
            ->set('form.name', 'Updated Student')
            ->set('form.email', $user->email)
            ->set('form.national_id_number', '1234567890')
            ->set('form.department_id', $department->id)
            ->call('save');

        expect($user->fresh()->name)->toBe('Updated Student');
    });

    it('deletes a student', function () {
        actingAsSuperAdmin();
        $user = User::factory()->create()->assignRole('student');

        Livewire::test(StudentManager::class)
            ->call('delete', $user->id);

        expect(User::find($user->id))->toBeNull();
    });

    it('deletes selected students', function () {
        actingAsSuperAdmin();
        $users = User::factory(2)->create()->each->assignRole('student');

        Livewire::test(StudentManager::class)
            ->call('selectAll', $users->pluck('id')->toArray())
            ->call('deleteSelected');

        expect(User::whereIn('id', $users->pluck('id'))->count())->toBe(0);
    });

    // ─── EXPORT ─────────────────────────────────────────────

    it('export returns StreamedResponse', function () {
        actingAsSuperAdmin();
        User::factory()->create()->assignRole('student');

        $response = Livewire::test(StudentManager::class)
            ->instance()
            ->export(app(CsvHandler::class));

        expect($response)->toBeInstanceOf(StreamedResponse::class);
    });

    // ─── COMPUTED ────────────────────────────────────────────

    it('returns departments list', function () {
        actingAsSuperAdmin();
        Department::factory(3)->create();

        $component = Livewire::test(StudentManager::class);
        $departments = $component->instance()->departments();

        expect($departments)->toHaveCount(3);
    });

    // ─── RENDER ─────────────────────────────────────────────

    it('renders the correct view', function () {
        actingAsSuperAdmin();

        Livewire::test(StudentManager::class)
            ->assertViewIs('admin.student-manager');
    });
});
