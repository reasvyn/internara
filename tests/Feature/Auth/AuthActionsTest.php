<?php

declare(strict_types=1);

use App\Domain\Auth\Actions\ConfirmPasswordAction;
use App\Domain\Auth\Actions\DetectUserAccountCloneAction;
use App\Domain\Auth\Actions\GenerateRecoverySlipAction;
use App\Domain\Auth\Actions\LockUserAccountAction;
use App\Domain\Auth\Actions\LoginAction;
use App\Domain\Auth\Actions\RedeemRecoverySlipAction;
use App\Domain\Auth\Actions\ResetUserPasswordAction;
use App\Domain\Auth\Actions\SendPasswordResetLinkAction;
use App\Domain\Auth\Actions\UnlockUserAccountAction;
use App\Domain\Auth\Actions\UpdateRolePermissionsAction;
use App\Domain\Auth\Actions\UpdateUserPasswordAction;
use App\Domain\Auth\Enums\AccountStatus;
use App\Domain\Auth\Enums\Role;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    collect(Role::cases())->each(fn ($r) => RoleModel::create(['name' => $r->value, 'guard_name' => 'web']));
});

describe('AuthDomainActions', function () {
    describe('ConfirmPasswordAction', function () {
        it('confirms correct password', function () {
            $user = User::factory()->create(['password' => Hash::make('correct-password')]);

            app(ConfirmPasswordAction::class)->execute($user, 'correct-password');

            expect(session('auth.password_confirmed_at'))->not->toBeNull();
        });

        it('throws on wrong password', function () {
            $user = User::factory()->create(['password' => Hash::make('correct-password')]);

            app(ConfirmPasswordAction::class)->execute($user, 'wrong-password');
        })->throws(RuntimeException::class);
    });

    describe('LoginAction', function () {
        it('logs in with email', function () {
            $user = User::factory()->create([
                'email' => 'test@example.com',
                'password' => Hash::make('password'),
            ]);
            $user->setStatus(AccountStatus::VERIFIED->value);

            $result = app(LoginAction::class)->execute('test@example.com', 'password');

            expect($result->id)->toBe($user->id);
        });

        it('logs in with username', function () {
            $user = User::factory()->create([
                'username' => 'testuser',
                'password' => Hash::make('password'),
            ]);
            $user->setStatus(AccountStatus::VERIFIED->value);

            $result = app(LoginAction::class)->execute('testuser', 'password');

            expect($result->id)->toBe($user->id);
        });

        it('blocks suspended users', function () {
            $user = User::factory()->create([
                'email' => 'suspended@example.com',
                'password' => Hash::make('password'),
            ]);
            $user->setStatus(AccountStatus::SUSPENDED->value);

            app(LoginAction::class)->execute('suspended@example.com', 'password');
        })->throws(RuntimeException::class);
    });

    describe('DetectUserAccountCloneAction', function () {
        it('returns empty collection when no duplicates exist', function () {
            User::factory()->create(['email' => 'one@example.com']);
            User::factory()->create(['email' => 'two@example.com']);

            $result = app(DetectUserAccountCloneAction::class)->execute();

            expect($result)->toBeEmpty();
        });

        it('returns empty on unique email data', function () {
            User::factory()->create(['email' => 'unique@example.com']);
            User::factory()->create(['email' => 'different@example.com']);

            $result = app(DetectUserAccountCloneAction::class)->execute();

            expect($result)->toBeEmpty();
        });
    });

    describe('GenerateRecoverySlipAction', function () {
        it('generates recovery codes for user', function () {
            $user = User::factory()->create();

            $result = app(GenerateRecoverySlipAction::class)->execute($user);

            expect($result)->toHaveKeys(['code', 'plaintext', 'expires_at'])
                ->and($result['plaintext'])->toHaveCount(GenerateRecoverySlipAction::CODE_COUNT)
                ->and($result['code']->user_id)->toBe($user->id);
        });
    });

    describe('RedeemRecoverySlipAction', function () {
        it('redeems valid recovery code', function () {
            $user = User::factory()->create(['username' => 'redeemuser']);
            $codes = app(GenerateRecoverySlipAction::class)->execute($user);
            $plaintext = $codes['plaintext'][0];

            $result = app(RedeemRecoverySlipAction::class)->execute('redeemuser', $plaintext, 'new-password-123');

            expect($result->id)->toBe($user->id)
                ->and(Hash::check('new-password-123', $result->fresh()->password))->toBeTrue();
        });

        it('throws on invalid code', function () {
            $user = User::factory()->create(['username' => 'failuser']);

            app(RedeemRecoverySlipAction::class)->execute('failuser', 'INVALID-CODE', 'new-password');
        })->throws(RuntimeException::class);
    });

    describe('LockUserAccountAction', function () {
        it('locks a user account', function () {
            $user = User::factory()->create(['locked_at' => null]);

            app(LockUserAccountAction::class)->execute($user);

            expect($user->fresh()->locked_at)->not->toBeNull();
        });

        it('does nothing when already locked', function () {
            $lockedAt = now();
            $user = User::factory()->create(['locked_at' => $lockedAt]);

            app(LockUserAccountAction::class)->execute($user);

            expect($user->fresh()->locked_at->toIso8601String())->toBe($lockedAt->toIso8601String());
        });
    });

    describe('UnlockUserAccountAction', function () {
        it('unlocks a locked user account', function () {
            $user = User::factory()->create(['locked_at' => now(), 'locked_reason' => 'test']);

            app(UnlockUserAccountAction::class)->execute($user);

            expect($user->fresh()->locked_at)->toBeNull()
                ->and($user->fresh()->locked_reason)->toBeNull();
        });

        it('does nothing when already unlocked', function () {
            $user = User::factory()->create(['locked_at' => null]);

            app(UnlockUserAccountAction::class)->execute($user);

            expect($user->fresh()->locked_at)->toBeNull();
        });
    });

    describe('ResetUserPasswordAction', function () {
        it('resets user password and returns new password', function () {
            $user = User::factory()->create();

            $result = app(ResetUserPasswordAction::class)->execute($user);

            expect($result)->toHaveKeys(['user', 'new_password'])
                ->and($result['user']->id)->toBe($user->id)
                ->and(Hash::check($result['new_password'], $result['user']->fresh()->password))->toBeTrue();
        });
    });

    describe('UpdateUserPasswordAction', function () {
        it('updates user password', function () {
            $user = User::factory()->create();

            app(UpdateUserPasswordAction::class)->execute($user, 'new-secure-password');

            expect(Hash::check('new-secure-password', $user->fresh()->password))->toBeTrue();
        });

        it('validates minimum password length', function () {
            $user = User::factory()->create();

            app(UpdateUserPasswordAction::class)->execute($user, 'short');
        })->throws(ValidationException::class);
    });

    describe('SendPasswordResetLinkAction', function () {
        it('sends reset link for existing email', function () {
            User::factory()->create(['email' => 'reset@example.com']);

            $status = app(SendPasswordResetLinkAction::class)->execute('reset@example.com');

            expect($status)->toBeString();
        });
    });

    describe('UpdateRolePermissionsAction', function () {
        it('syncs permissions to a role', function () {
            $role = RoleModel::create(['name' => 'custom_role', 'guard_name' => 'web']);
            $perm1 = Permission::create(['name' => 'view_users', 'guard_name' => 'web']);
            $perm2 = Permission::create(['name' => 'edit_users', 'guard_name' => 'web']);

            app(UpdateRolePermissionsAction::class)->execute($role, ['view_users', 'edit_users']);

            expect($role->fresh()->hasPermissionTo('view_users'))->toBeTrue()
                ->and($role->fresh()->hasPermissionTo('edit_users'))->toBeTrue();
        });
    });
});
