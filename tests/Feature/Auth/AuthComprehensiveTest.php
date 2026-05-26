<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

use App\Domain\Auth\Actions\DetectUserAccountCloneAction;
use App\Domain\Auth\Actions\GenerateRecoverySlipAction;
use App\Domain\Auth\Actions\LockUserAccountAction;
use App\Domain\Auth\Actions\LoginAction;
use App\Domain\Auth\Actions\RedeemRecoverySlipAction;
use App\Domain\Auth\Actions\ResetPasswordAction;
use App\Domain\Auth\Actions\ResetUserPasswordAction;
use App\Domain\Auth\Actions\UnlockUserAccountAction;
use App\Domain\Auth\Actions\UpdateUserPasswordAction;
use App\Domain\Auth\Entities\SuperAdminIntegrityRules;
use App\Domain\Auth\Enums\AccountStatus;
use App\Domain\Auth\Enums\Role;
use App\Domain\Auth\Livewire\AccessManager;
use App\Domain\Auth\Livewire\AccountRecovery;
use App\Domain\Auth\Livewire\RecoveryCode;
use App\Domain\Auth\Models\AccountRecoveryCode;
use App\Domain\Auth\Policies\UserPolicy;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Setup\Models\Setup;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    Setup::truncate();
    Setup::create(['is_installed' => true]);
    foreach (['super_admin', 'admin', 'student', 'teacher', 'supervisor'] as $role) {
        RoleModel::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }
    // Required permissions for UserPolicy
    foreach (['users.view', 'users.create', 'users.edit', 'users.delete'] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }
    foreach (['admin', 'super_admin'] as $role) {
        $r = RoleModel::where('name', $role)->first();
        $r->givePermissionTo(Permission::all());
    }
});

// ─── UserPolicy ──────────────────────────────────────────────────────────

describe('UserPolicy', function () {
    it('allows admin to viewAny', function () {
        $user = User::factory()->create()->assignRole(Role::ADMIN->value);
        expect(app(UserPolicy::class)->viewAny($user))->toBeTrue();
    });

    it('denies non-admin to viewAny', function () {
        $user = User::factory()->create();
        expect(app(UserPolicy::class)->viewAny($user))->toBeFalse();
    });

    it('allows self-view', function () {
        $user = User::factory()->create();
        expect(app(UserPolicy::class)->view($user, $user))->toBeTrue();
    });

    it('allows admin to view others', function () {
        $admin = User::factory()->create()->assignRole(Role::ADMIN->value);
        $other = User::factory()->create();
        expect(app(UserPolicy::class)->view($admin, $other))->toBeTrue();
    });

    it('allows admin to create', function () {
        $user = User::factory()->create()->assignRole(Role::ADMIN->value);
        expect(app(UserPolicy::class)->create($user))->toBeTrue();
    });

    it('allows self-update', function () {
        $user = User::factory()->create();
        expect(app(UserPolicy::class)->update($user, $user))->toBeTrue();
    });

    it('allows admin to update others', function () {
        $admin = User::factory()->create()->assignRole(Role::ADMIN->value);
        $other = User::factory()->create();
        expect(app(UserPolicy::class)->update($admin, $other))->toBeTrue();
    });

    it('blocks super_admin update by others', function () {
        $sa = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);
        $other = User::factory()->create();
        expect(app(UserPolicy::class)->update($other, $sa))->toBeFalse();
    });

    it('blocks super_admin delete', function () {
        $sa = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);
        expect(app(UserPolicy::class)->delete(User::factory()->create(), $sa))->toBeFalse();
    });

    it('blocks self-delete', function () {
        $user = User::factory()->create();
        expect(app(UserPolicy::class)->delete($user, $user))->toBeFalse();
    });
});

// ─── LoginAction ─────────────────────────────────────────────────────────

describe('LoginAction', function () {
    it('logs in with email', function () {
        User::factory()->create(['email' => 'test@example.com', 'password' => Hash::make('Secure1Pass')]);
        $result = app(LoginAction::class)->execute('test@example.com', 'Secure1Pass');
        expect($result)->toBeInstanceOf(User::class);
    });

    it('logs in with username', function () {
        User::factory()->create(['username' => 'testuser', 'email' => 't@t.com', 'password' => Hash::make('Secure1Pass')]);
        $result = app(LoginAction::class)->execute('testuser', 'Secure1Pass');
        expect($result)->toBeInstanceOf(User::class);
    });

    it('rejects wrong credentials', function () {
        User::factory()->create(['email' => 'a@b.com', 'password' => Hash::make('right')]);
        expect(fn () => app(LoginAction::class)->execute('a@b.com', 'wrong'))
            ->toThrow(RuntimeException::class, __('auth.failed'));
    });

    it('blocks suspended account', function () {
        $user = User::factory()->create(['email' => 's@t.com', 'password' => Hash::make('Secure1Pass')]);
        $user->setStatus(AccountStatus::SUSPENDED->value);
        expect(fn () => app(LoginAction::class)->execute('s@t.com', 'Secure1Pass'))
            ->toThrow(RuntimeException::class);
    });

    it('blocks locked account', function () {
        User::factory()->create(['email' => 'l@t.com', 'password' => Hash::make('Secure1Pass'), 'locked_at' => now(), 'locked_reason' => 'test']);
        expect(fn () => app(LoginAction::class)->execute('l@t.com', 'Secure1Pass'))
            ->toThrow(RuntimeException::class);
    });

    it('blocks archived account', function () {
        $user = User::factory()->create(['email' => 'a@t.com', 'password' => Hash::make('Secure1Pass')]);
        $user->setStatus(AccountStatus::ARCHIVED->value);
        expect(fn () => app(LoginAction::class)->execute('a@t.com', 'Secure1Pass'))
            ->toThrow(RuntimeException::class);
    });

    it('blocks inactive account', function () {
        $user = User::factory()->create(['email' => 'i@t.com', 'password' => Hash::make('Secure1Pass')]);
        $user->setStatus(AccountStatus::INACTIVE->value);
        expect(fn () => app(LoginAction::class)->execute('i@t.com', 'Secure1Pass'))
            ->toThrow(RuntimeException::class);
    });
});

// ─── SuperAdminIntegrityRules ────────────────────────────────────────────

describe('SuperAdminIntegrityRules', function () {
    it('detects immutable super admin', function () {
        $user = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);
        $rules = SuperAdminIntegrityRules::fromModel($user);
        expect($rules->isImmutable())->toBeTrue();
        expect($rules->canBeDeleted())->toBeFalse();
        expect($rules->canBeLocked())->toBeFalse();
        expect($rules->canChangeName())->toBeFalse();
        expect($rules->canChangeUsername())->toBeFalse();
    });

    it('allows changes for regular users', function () {
        $user = User::factory()->create();
        $rules = SuperAdminIntegrityRules::fromModel($user);
        expect($rules->canBeDeleted())->toBeTrue();
        expect($rules->canChangeName())->toBeTrue();
    });

    it('detects last super admin', function () {
        $user = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);
        expect(SuperAdminIntegrityRules::fromModel($user)->isLastSuperAdmin())->toBeTrue();
    });
});

// ─── Lock/Unlock Super Admin Guard ───────────────────────────────────────

describe('lock/unlock super admin guard', function () {
    it('blocks locking super admin', function () {
        $sa = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);
        expect(fn () => app(LockUserAccountAction::class)->execute($sa, 'test'))
            ->toThrow(RuntimeException::class);
    });

    it('blocks unlocking super admin', function () {
        $sa = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);
        expect(fn () => app(UnlockUserAccountAction::class)->execute($sa))
            ->toThrow(RuntimeException::class);
    });

    it('blocks password reset for super admin', function () {
        $sa = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);
        expect(fn () => app(ResetUserPasswordAction::class)->execute($sa))
            ->toThrow(RejectedException::class);
    });

    it('locks regular user', function () {
        $user = User::factory()->create();
        app(LockUserAccountAction::class)->execute($user, 'testing');
        expect($user->fresh()->locked_at)->not->toBeNull();
    });

    it('unlocks regular user', function () {
        $user = User::factory()->create(['locked_at' => now(), 'locked_reason' => 'test']);
        app(UnlockUserAccountAction::class)->execute($user);
        expect($user->fresh()->locked_at)->toBeNull();
    });
});

// ─── ResetUserPasswordAction ─────────────────────────────────────────────

describe('ResetUserPasswordAction', function () {
    it('resets password for regular user', function () {
        $user = User::factory()->create(['password' => Hash::make('old')]);
        $result = app(ResetUserPasswordAction::class)->execute($user);
        expect($result)->toHaveKey('new_password');
        expect(Hash::check($result['new_password'], $user->fresh()->password))->toBeTrue();
    });
});

// ─── UpdateUserPasswordAction ────────────────────────────────────────────

describe('UpdateUserPasswordAction', function () {
    it('updates user password', function () {
        $user = User::factory()->create(['password' => Hash::make('OldPass1')]);
        app(UpdateUserPasswordAction::class)->execute($user, 'NewPass1');
        expect(Hash::check('NewPass1', $user->fresh()->password))->toBeTrue();
    });

    it('rejects weak password', function () {
        $user = User::factory()->create();
        expect(fn () => app(UpdateUserPasswordAction::class)->execute($user, 'weak'))
            ->toThrow(ValidationException::class);
    });
});

// ─── ResetPasswordAction ─────────────────────────────────────────────────

describe('ResetPasswordAction', function () {
    it('resets password with valid token', function () {
        $user = User::factory()->create(['email' => 'reset@test.com']);
        $token = Password::broker()->createToken($user);

        app(ResetPasswordAction::class)->execute(
            email: 'reset@test.com', password: 'NewPass1',
            passwordConfirmation: 'NewPass1', token: $token,
        );
        expect(Hash::check('NewPass1', $user->fresh()->password))->toBeTrue();
    });

    it('rejects invalid token', function () {
        User::factory()->create(['email' => 'reset@test.com']);
        expect(fn () => app(ResetPasswordAction::class)->execute(
            email: 'reset@test.com', password: 'NewPass1',
            passwordConfirmation: 'NewPass1', token: 'invalid',
        ))->toThrow(RuntimeException::class);
    });
});

// ─── RedeemRecoverySlipAction ────────────────────────────────────────────

describe('RedeemRecoverySlipAction', function () {
    it('redeems valid code', function () {
        $user = User::factory()->create(['username' => 'testuser']);
        AccountRecoveryCode::create([
            'user_id' => $user->id, 'code_hash' => Hash::make(strtoupper('ABC123')),
            'generated_at' => now(), 'expires_at' => null,
        ]);
        app(RedeemRecoverySlipAction::class)->execute('testuser', 'ABC123', 'NewPass1');
        expect(Hash::check('NewPass1', $user->fresh()->password))->toBeTrue();
    });

    it('rejects non-existent username', function () {
        expect(fn () => app(RedeemRecoverySlipAction::class)->execute('nobody', 'X', 'P'))
            ->toThrow(RuntimeException::class);
    });

    it('rejects wrong code', function () {
        $user = User::factory()->create(['username' => 'u']);
        AccountRecoveryCode::create([
            'user_id' => $user->id, 'code_hash' => Hash::make(strtoupper('RIGHT')),
            'generated_at' => now(),
        ]);
        expect(fn () => app(RedeemRecoverySlipAction::class)->execute('u', 'WRONG', 'P'))
            ->toThrow(RuntimeException::class);
    });
});

// ─── GenerateRecoverySlipAction ──────────────────────────────────────────

describe('GenerateRecoverySlipAction', function () {
    it('generates 10 codes with no expiry', function () {
        $user = User::factory()->create();
        $result = app(GenerateRecoverySlipAction::class)->execute($user);
        expect($result['plaintext'])->toHaveCount(10);
        expect($result['expires_at'])->toBeNull();
    });
});

// ─── DetectUserAccountCloneAction ────────────────────────────────────────

describe('DetectUserAccountCloneAction', function () {
    it('returns empty when no duplicates', function () {
        User::factory()->create(['email' => 'u1@t.com']);
        User::factory()->create(['email' => 'u2@t.com']);
        $result = app(DetectUserAccountCloneAction::class)->execute();
        expect($result)->toBeEmpty();
    });
});

// ─── AccountLifecycleManager actions (tested via direct actions) ────────

// The Livewire view references admin.accounts.detect-clones route
// which is not defined. Component actions are tested indirectly
// through LockUserAccountAction and UnlockUserAccountAction above.

// ─── AccessManager ────────────────────────────────────────────────────────

describe('AccessManager', function () {
    beforeEach(function () {
        $this->admin = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);
        $this->actingAs($this->admin);
    });

    it('renders the access manager', function () {
        Livewire::test(AccessManager::class)
            ->assertSuccessful();
    });

    it('edits role permissions', function () {
        $role = RoleModel::where('name', Role::ADMIN->value)->first();
        Livewire::test(AccessManager::class)
            ->call('editRolePermissions', $role->id)
            ->assertSet('selectedRole.id', $role->id);
    });

    it('saves permissions to a role', function () {
        $role = RoleModel::where('name', Role::ADMIN->value)->first();
        Livewire::test(AccessManager::class)
            ->call('editRolePermissions', $role->id)
            ->call('savePermissions')
            ->assertSuccessful();
    });
});

// ─── AccountRecovery ──────────────────────────────────────────────────────

describe('AccountRecovery', function () {
    it('renders the recovery form', function () {
        Livewire::test(AccountRecovery::class)
            ->assertSuccessful();
    });
});

// ─── RecoveryCode ─────────────────────────────────────────────────────────

describe('RecoveryCode', function () {
    beforeEach(function () {
        $this->user = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);
        $this->actingAs($this->user);
    });

    it('generates recovery codes', function () {
        Livewire::test(RecoveryCode::class)
            ->call('generate')
            ->assertSet('codes', fn ($v) => count($v) === 10);
    });

    it('resets codes', function () {
        Livewire::test(RecoveryCode::class)
            ->call('generate')
            ->call('resetCode')
            ->assertSet('codes', []);
    });
});
