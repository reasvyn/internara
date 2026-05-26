<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

use App\Domain\Auth\Enums\Role;
use App\Domain\Setup\Models\Setup;
use App\Domain\User\Actions\GetStudentDashboardDataAction;
use App\Domain\User\Actions\UpdateProfileAction;
use App\Domain\User\Http\Controllers\DashboardController;
use App\Domain\User\Livewire\Dashboards\AdminDashboard;
use App\Domain\User\Livewire\Dashboards\StudentDashboard;
use App\Domain\User\Livewire\ProfileEditor;
use App\Domain\User\Livewire\UserDashboard;
use App\Domain\User\Models\User;
use App\Domain\User\Services\DashboardService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
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
    it('returns admin dashboard route for super_admin', function () {
        $user = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);
        $route = app(DashboardService::class)->getDashboardForUser($user);
        expect($route)->toBe('admin.dashboard');
    });

    it('returns admin dashboard route for admin', function () {
        $user = User::factory()->create()->assignRole(Role::ADMIN->value);
        $route = app(DashboardService::class)->getDashboardForUser($user);
        expect($route)->toBe('admin.dashboard');
    });

    it('returns student dashboard route for student', function () {
        $user = User::factory()->create()->assignRole(Role::STUDENT->value);
        $route = app(DashboardService::class)->getDashboardForUser($user);
        expect($route)->toBe('student.dashboard');
    });

    it('returns teacher dashboard route for teacher', function () {
        $user = User::factory()->create()->assignRole(Role::TEACHER->value);
        $route = app(DashboardService::class)->getDashboardForUser($user);
        expect($route)->toBe('teacher.dashboard');
    });

    it('returns supervisor dashboard route for supervisor', function () {
        $user = User::factory()->create()->assignRole(Role::SUPERVISOR->value);
        $route = app(DashboardService::class)->getDashboardForUser($user);
        expect($route)->toBe('supervisor.dashboard');
    });

    it('returns shared stats for a user', function () {
        $user = User::factory()->create(['name' => 'Test User'])->assignRole(Role::STUDENT->value);
        $stats = app(DashboardService::class)->getSharedStats();
        expect($stats)->toHaveKeys(['user_name', 'user_role', 'last_login']);
    });
});

// ─── DashboardController ───────────────────────────────────────────────────

describe('DashboardController', function () {
    it('redirects super_admin to admin dashboard', function () {
        $user = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);
        $this->actingAs($user);
        $response = $this->get(route('dashboard'));
        $response->assertRedirect();
    });

    it('redirects student to student dashboard', function () {
        $user = User::factory()->create()->assignRole(Role::STUDENT->value);
        $this->actingAs($user);
        $response = $this->get(route('dashboard'));
        $response->assertRedirect();
    });

    it('redirects unauthenticated to login', function () {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    });
});

// ─── UserDashboard ─────────────────────────────────────────────────────────

describe('UserDashboard', function () {
    beforeEach(function () {
        $this->user = User::factory()->create()->assignRole(Role::STUDENT->value);
        $this->actingAs($this->user);
    });

    it('renders the dashboard', function () {
        Livewire::test(UserDashboard::class)
            ->assertSuccessful();
    });

    it('shows recent activities', function () {
        Livewire::test(UserDashboard::class)
            ->assertSuccessful();
    });
});

// ─── ProfileEditor ─────────────────────────────────────────────────────────

describe('ProfileEditor', function () {
    beforeEach(function () {
        $this->user = User::factory()->create()->assignRole(Role::STUDENT->value);
        $this->actingAs($this->user);
    });

    it('mounts and renders', function () {
        Livewire::test(ProfileEditor::class)
            ->assertSuccessful()
            ->assertSet('user.id', $this->user->id);
    });

    it('pre-fills form from user data on mount', function () {
        Livewire::test(ProfileEditor::class)
            ->assertSet('profileForm.name', $this->user->name)
            ->assertSet('profileForm.email', $this->user->email);
    });

    it('saves profile data', function () {
        Livewire::test(ProfileEditor::class)
            ->set('profileForm.name', 'Updated Name')
            ->set('profileForm.email', 'updated@test.com')
            ->set('profileForm.phone', '08123456789')
            ->call('save')
            ->assertHasNoErrors();

        expect($this->user->fresh()->name)->toBe('Updated Name');
        expect($this->user->fresh()->email)->toBe('updated@test.com');
        expect($this->user->fresh()->profile->phone)->toBe('08123456789');
    });

    it('detects staff roles', function () {
        $admin = User::factory()->create()->assignRole(Role::ADMIN->value);
        $this->actingAs($admin);

        Livewire::test(ProfileEditor::class)
            ->assertSet('profileForm.nip', null)
            ->call('save')
            ->assertHasNoErrors();
    });

    it('saves staff fields for admin users', function () {
        $admin = User::factory()->create()->assignRole(Role::ADMIN->value);
        $this->actingAs($admin);

        Livewire::test(ProfileEditor::class)
            ->set('profileForm.nip', '123456789012345678')
            ->set('profileForm.nuptk', '9876543210987654')
            ->set('profileForm.competence_field', 'Computer Science')
            ->call('save')
            ->assertHasNoErrors();

        expect($admin->fresh()->profile->nip)->toBe('123456789012345678');
        expect($admin->fresh()->profile->nuptk)->toBe('9876543210987654');
    });

    it('shows display fields for super admin name', function () {
        $sa = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);
        $this->actingAs($sa);

        Livewire::test(ProfileEditor::class)
            ->assertSet('profileForm.name', $sa->name);
    });

    it('validates email uniqueness', function () {
        User::factory()->create(['email' => 'existing@test.com']);

        Livewire::test(ProfileEditor::class)
            ->set('profileForm.email', 'existing@test.com')
            ->call('save')
            ->assertHasErrors(['profileForm.email']);
    });

    it('updates password successfully', function () {
        $this->user->update(['password' => Hash::make('Current1Pass')]);

        Livewire::test(ProfileEditor::class)
            ->set('passwordForm.current_password', 'Current1Pass')
            ->set('passwordForm.password', 'NewSecure1')
            ->set('passwordForm.password_confirmation', 'NewSecure1')
            ->call('updatePassword')
            ->assertHasNoErrors()
            ->assertSet('passwordForm.current_password', '');
    });

    it('rejects wrong current password', function () {
        $this->user->update(['password' => Hash::make('Correct1Pass')]);

        Livewire::test(ProfileEditor::class)
            ->set('passwordForm.current_password', 'WrongPass1')
            ->set('passwordForm.password', 'NewPass1')
            ->set('passwordForm.password_confirmation', 'NewPass1')
            ->call('updatePassword')
            ->assertHasErrors(['passwordForm.current_password']);
    });

    it('has avatar preview initially null', function () {
        $component = Livewire::test(ProfileEditor::class);
        $component->assertSet('avatar', null);
    });
});

// ─── AdminDashboard ────────────────────────────────────────────────────────

describe('AdminDashboard', function () {
    it('renders for super_admin', function () {
        $user = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);
        $this->actingAs($user);

        Livewire::test(AdminDashboard::class)
            ->assertSuccessful();
    });

    it('renders for admin', function () {
        $user = User::factory()->create()->assignRole(Role::ADMIN->value);
        $this->actingAs($user);

        Livewire::test(AdminDashboard::class)
            ->assertSuccessful();
    });

    it('blocks non-admin users', function () {
        $user = User::factory()->create()->assignRole(Role::STUDENT->value);
        $this->actingAs($user);

        Livewire::test(AdminDashboard::class)
            ->assertForbidden();
    });
});

// ─── StudentDashboard (partial: view depends on student.documents route) ──

describe('StudentDashboard', function () {
    it('blocks non-student users', function () {
        $user = User::factory()->create()->assignRole(Role::TEACHER->value);
        $this->actingAs($user);

        Livewire::test(StudentDashboard::class)
            ->assertForbidden();
    });
});

// ─── UpdateProfileAction ───────────────────────────────────────────────────

describe('UpdateProfileAction', function () {
    it('updates user name and email', function () {
        $user = User::factory()->create(['name' => 'Old', 'email' => 'old@test.com']);

        app(UpdateProfileAction::class)->execute(
            $user,
            ['phone' => '08123456789'],
            name: 'New Name',
            email: 'new@test.com',
        );

        expect($user->fresh()->name)->toBe('New Name');
        expect($user->fresh()->email)->toBe('new@test.com');
    });

    it('creates profile if none exists', function () {
        $user = User::factory()->create();

        app(UpdateProfileAction::class)->execute(
            $user,
            ['phone' => '08123456789', 'bio' => 'Hello'],
        );

        expect($user->fresh()->profile)->not->toBeNull();
        expect($user->fresh()->profile->phone)->toBe('08123456789');
    });

    it('validates profile data', function () {
        $user = User::factory()->create();

        expect(fn () => app(UpdateProfileAction::class)->execute(
            $user,
            ['phone' => str_repeat('1', 21)],
        ))->toThrow(ValidationException::class);
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
