<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Core\Support\PasswordRules;
use App\Domain\User\Actions\GetStudentDashboardDataAction;
use App\Domain\User\Actions\SendNotificationAction;
use App\Domain\User\Actions\UpdateProfileAction;
use App\Domain\User\Models\User;
use App\Domain\User\Rules\ReservedAuthoritativeName;
use App\Domain\User\Rules\SystemUsername;
use App\Domain\User\Services\DashboardService;
use App\Domain\User\Support\UserIdentifierGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role as RoleModel;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['super_admin', 'admin', 'student', 'teacher', 'supervisor'] as $role) {
        RoleModel::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }
    app()->setLocale('en');
});

// ─── Profile Update Security ────────────────────────────────────────────

describe('profile update security', function () {
    it('prevents super admin name change', function () {
        $sa = User::factory()->create(['name' => 'Administrator'])->assignRole(Role::SUPER_ADMIN->value);

        expect(fn () => app(UpdateProfileAction::class)->execute(
            $sa, [], name: 'Hacker',
        ))->toThrow(RejectedException::class);
    });

    it('allows regular user name change', function () {
        $user = User::factory()->create(['name' => 'Old Name'])->assignRole(Role::STUDENT->value);

        $profile = app(UpdateProfileAction::class)->execute(
            $user, [], name: 'New Name',
        );

        expect($user->fresh()->name)->toBe('New Name');
    });

    it('validates phone max length', function () {
        $user = User::factory()->create()->assignRole(Role::STUDENT->value);

        expect(fn () => app(UpdateProfileAction::class)->execute($user, [
            'phone' => str_repeat('1', 21),
        ]))->toThrow(ValidationException::class);
    });

    it('validates address max length', function () {
        $user = User::factory()->create()->assignRole(Role::STUDENT->value);

        expect(fn () => app(UpdateProfileAction::class)->execute($user, [
            'address' => str_repeat('a', 501),
        ]))->toThrow(ValidationException::class);
    });

    it('preserves empty string fields as empty', function () {
        $user = User::factory()->create()->assignRole(Role::STUDENT->value);
        $user->profile()->create(['phone' => '08123456789']);

        app(UpdateProfileAction::class)->execute($user, [
            'phone' => '',
            'address' => '',
        ]);

        expect($user->fresh()->profile->phone)->toBe('');
        expect($user->fresh()->profile->address)->toBe('');
    });
});

// ─── SendNotificationAction Validation ───────────────────────────────────

describe('SendNotificationAction validation', function () {
    it('requires type and title', function () {
        $user = User::factory()->create();

        expect(fn () => app(SendNotificationAction::class)->execute(
            userId: $user->id, type: '', title: '',
        ))->toThrow(ValidationException::class);
    });

    it('creates notification with valid data', function () {
        $user = User::factory()->create();

        $notification = app(SendNotificationAction::class)->execute(
            userId: $user->id, type: 'test', title: 'Hello',
        );

        expect($notification->is_read)->toBeFalse();
        expect($notification->type)->toBe('test');
    });
});

// ─── Dashboard Routing Security ─────────────────────────────────────────

describe('dashboard routing', function () {
    it('routes super_admin to admin dashboard', function () {
        $user = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);
        expect(app(DashboardService::class)->getDashboardForUser($user))->toBe('admin.dashboard');
    });

    it('routes student to student dashboard', function () {
        $user = User::factory()->create()->assignRole(Role::STUDENT->value);
        expect(app(DashboardService::class)->getDashboardForUser($user))->toBe('student.dashboard');
    });

    it('routes teacher to teacher dashboard', function () {
        $user = User::factory()->create()->assignRole(Role::TEACHER->value);
        expect(app(DashboardService::class)->getDashboardForUser($user))->toBe('teacher.dashboard');
    });

    it('routes supervisor to supervisor dashboard', function () {
        $user = User::factory()->create()->assignRole(Role::SUPERVISOR->value);
        expect(app(DashboardService::class)->getDashboardForUser($user))->toBe('supervisor.dashboard');
    });

    it('falls back to user dashboard for unknown role', function () {
        $user = User::factory()->create();
        expect(app(DashboardService::class)->getDashboardForUser($user))->toBe('user.dashboard');
    });
});

// ─── ReservedAuthoritativeName Rule ──────────────────────────────────────

describe('ReservedAuthoritativeName rule', function () {
    it('rejects reserved names', function () {
        $rule = new ReservedAuthoritativeName;
        $fail = fn ($msg) => throw new RuntimeException($msg);

        foreach (['admin', 'superadmin', 'root', 'system'] as $name) {
            expect(fn () => $rule->validate('username', $name, $fail))->toThrow(RuntimeException::class);
        }
    });

    it('allows normal names', function () {
        $rule = new ReservedAuthoritativeName;
        $fail = fn ($msg) => throw new RuntimeException($msg);

        $rule->validate('username', 'john_doe', $fail);
        expect(true)->toBeTrue();
    });
});

// ─── SystemUsername Rule ─────────────────────────────────────────────────

describe('SystemUsername rule', function () {
    it('rejects invalid formats', function () {
        $rule = new SystemUsername;
        $fail = fn ($msg) => throw new RuntimeException($msg);

        expect(fn () => $rule->validate('username', '123abc', $fail))->toThrow(RuntimeException::class);
        expect(fn () => $rule->validate('username', 'ab', $fail))->toThrow(RuntimeException::class);
    });

    it('accepts valid format', function () {
        $rule = new SystemUsername;
        $fail = fn ($msg) => throw new RuntimeException($msg);

        $rule->validate('username', 'johndoe123', $fail);
        expect(true)->toBeTrue();
    });
});

// ─── UserIdentifierGenerator ─────────────────────────────────────────────

describe('UserIdentifierGenerator', function () {
    it('generates unique usernames', function () {
        $usernames = [];
        for ($i = 0; $i < 10; $i++) {
            $usernames[] = UserIdentifierGenerator::generateUsername();
        }

        expect(array_unique($usernames))->toHaveCount(10);
    });

    it('generates username with correct length', function () {
        $username = UserIdentifierGenerator::generateUsername(12);
        expect(strlen($username))->toBe(13);
    });
});

// ─── PasswordRules Consistency ───────────────────────────────────────────

describe('PasswordRules consistency', function () {
    it('default() accepts valid password', function () {
        $validator = Validator::make(['password' => 'Valid1Pass'], [
            'password' => PasswordRules::default(),
        ]);
        expect($validator->passes())->toBeTrue();
    });

    it('default() rejects weak password', function () {
        $validator = Validator::make(['password' => 'weak'], [
            'password' => PasswordRules::default(),
        ]);
        expect($validator->fails())->toBeTrue();
    });

    it('defaultAsArray() accepts valid password', function () {
        $validator = Validator::make(['password' => 'Valid1Pass'], [
            'password' => PasswordRules::defaultAsArray(),
        ]);
        expect($validator->passes())->toBeTrue();
    });
});

// ─── Student Dashboard Data ──────────────────────────────────────────────

describe('GetStudentDashboardDataAction', function () {
    it('returns zero counts when no registration exists', function () {
        $user = User::factory()->create()->assignRole(Role::STUDENT->value);

        $data = app(GetStudentDashboardDataAction::class)->execute($user->id);

        expect($data['totalJournals'])->toBe(0);
        expect($data['verifiedJournals'])->toBe(0);
        expect($data['registration'])->toBeNull();
    });

    it('throws for non-existent user', function () {
        expect(fn () => app(GetStudentDashboardDataAction::class)->execute('non-existent-id')
        )->toThrow(RuntimeException::class);
    });
});
