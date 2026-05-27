<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Domain\Auth\Enums\Role;
use App\Domain\Setup\Models\Setup;
use App\Domain\User\Actions\GetStudentDashboardDataAction;
use App\Domain\User\Livewire\Dashboards\AdminDashboard;
use App\Domain\User\Livewire\Dashboards\StudentDashboard;
use App\Domain\User\Livewire\Dashboards\SupervisorDashboard;
use App\Domain\User\Livewire\Dashboards\TeacherDashboard;
use App\Domain\User\Livewire\UserDashboard;
use App\Domain\User\Models\User;
use App\Domain\User\Services\DashboardService;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    Setup::truncate();
    Setup::create(['is_installed' => true]);
    foreach (['super_admin', 'admin', 'student', 'teacher', 'supervisor'] as $role) {
        RoleModel::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }
});

// ─── DashboardService ──────────────────────────────────────────────────────

describe('DashboardService', function () {
    it('returns admin dashboard for super_admin', function () {
        $user = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);
        expect(app(DashboardService::class)->getDashboardForUser($user))->toBe('admin.dashboard');
    });

    it('returns admin dashboard for admin', function () {
        $user = User::factory()->create()->assignRole(Role::ADMIN->value);
        expect(app(DashboardService::class)->getDashboardForUser($user))->toBe('admin.dashboard');
    });

    it('returns student dashboard for student', function () {
        $user = User::factory()->create()->assignRole(Role::STUDENT->value);
        expect(app(DashboardService::class)->getDashboardForUser($user))->toBe('student.dashboard');
    });

    it('returns teacher dashboard for teacher', function () {
        $user = User::factory()->create()->assignRole(Role::TEACHER->value);
        expect(app(DashboardService::class)->getDashboardForUser($user))->toBe('teacher.dashboard');
    });

    it('returns supervisor dashboard for supervisor', function () {
        $user = User::factory()->create()->assignRole(Role::SUPERVISOR->value);
        expect(app(DashboardService::class)->getDashboardForUser($user))->toBe('supervisor.dashboard');
    });

    it('returns shared stats', function () {
        $user = User::factory()->create(['name' => 'Test'])->assignRole(Role::STUDENT->value);
        $this->actingAs($user);
        $stats = app(DashboardService::class)->getSharedStats();
        expect($stats)->toHaveKeys(['user_name', 'user_role']);
    });
});

// ─── DashboardController ──────────────────────────────────────────────────

describe('DashboardController', function () {
    it('redirects super_admin to admin dashboard', function () {
        $user = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);
        $this->actingAs($user);
        $this->get(route('dashboard'))->assertRedirect();
    });

    it('redirects student to student dashboard', function () {
        $user = User::factory()->create()->assignRole(Role::STUDENT->value);
        $this->actingAs($user);
        $this->get(route('dashboard'))->assertRedirect();
    });

    it('redirects unauthenticated to login', function () {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    });
});

// ─── UserDashboard ─────────────────────────────────────────────────────────

describe('UserDashboard', function () {
    beforeEach(function () {
        $this->actingAs(User::factory()->create()->assignRole(Role::STUDENT->value));
    });

    it('renders', function () {
        Livewire::test(UserDashboard::class)
            ->assertSuccessful();
    });
});

// ─── AdminDashboard ────────────────────────────────────────────────────────

describe('AdminDashboard', function () {
    it('renders for super_admin', function () {
        $this->actingAs(User::factory()->create()->assignRole(Role::SUPER_ADMIN->value));
        Livewire::test(AdminDashboard::class)
            ->assertSuccessful();
    });

    it('renders for admin', function () {
        $this->actingAs(User::factory()->create()->assignRole(Role::ADMIN->value));
        Livewire::test(AdminDashboard::class)
            ->assertSuccessful();
    });

    it('blocks non-admin users', function () {
        $this->actingAs(User::factory()->create()->assignRole(Role::STUDENT->value));
        Livewire::test(AdminDashboard::class)
            ->assertForbidden();
    });
});

// ─── StudentDashboard ──────────────────────────────────────────────────────

describe('StudentDashboard', function () {
    it('renders for student', function () {
        $this->actingAs(User::factory()->create()->assignRole(Role::STUDENT->value));
        Livewire::test(StudentDashboard::class)
            ->assertSuccessful();
    });

    it('blocks non-student users', function () {
        $this->actingAs(User::factory()->create()->assignRole(Role::TEACHER->value));
        Livewire::test(StudentDashboard::class)
            ->assertForbidden();
    });
});

// ─── TeacherDashboard (auth only) ─────────────────────────────────────────

describe('TeacherDashboard', function () {
    it('blocks non-teacher users', function () {
        $this->actingAs(User::factory()->create()->assignRole(Role::STUDENT->value));
        Livewire::test(TeacherDashboard::class)
            ->assertForbidden();
    });
});

// ─── SupervisorDashboard ──────────────────────────────────────────────────

describe('SupervisorDashboard', function () {
    it('renders for supervisor', function () {
        $this->actingAs(User::factory()->create()->assignRole(Role::SUPERVISOR->value));
        Livewire::test(SupervisorDashboard::class)
            ->assertSuccessful();
    });

    it('blocks non-supervisor users', function () {
        $this->actingAs(User::factory()->create()->assignRole(Role::STUDENT->value));
        Livewire::test(SupervisorDashboard::class)
            ->assertForbidden();
    });
});

// ─── GetStudentDashboardDataAction ─────────────────────────────────────────

describe('GetStudentDashboardDataAction', function () {
    it('returns empty data for user without registration', function () {
        $user = User::factory()->create();
        $data = app(GetStudentDashboardDataAction::class)->execute($user->id);
        expect($data['registration'])->toBeNull();
        expect($data['totalJournals'])->toBe(0);
    });
});
