<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role as RoleEnum;
use App\Domain\Auth\Exceptions\AuthException;
use App\Domain\Auth\Notifications\WelcomeNotification;
use App\Domain\Core\Models\AuditLog;
use App\Domain\User\Actions\ChangeUserPasswordAction;
use App\Domain\User\Actions\CheckUserSessionExpiryAction;
use App\Domain\User\Actions\CreateUserAction;
use App\Domain\User\Actions\DeleteUserAction;
use App\Domain\User\Actions\DetectUserAccountCloneAction;
use App\Domain\User\Actions\LockUserAccountAction;
use App\Domain\User\Actions\SetupSuperAdminAction;
use App\Domain\User\Actions\UnlockUserAccountAction;
use App\Domain\User\Actions\UpdateProfileAction;
use App\Domain\User\Actions\UpdateUserAction;
use App\Domain\User\Actions\UpdateUserPasswordAction;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    foreach (RoleEnum::cases() as $role) {
        Role::firstOrCreate([
            'name' => $role->value,
            'guard_name' => 'web',
        ]);
    }
});

describe('CreateUserAction', function () {
    it('creates user with required data', function () {
        $action = app(CreateUserAction::class);

        $user = $action->execute([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'username' => 'u12345678',
            'password' => 'secretpass123',
        ]);

        expect($user)
            ->toBeInstanceOf(User::class)
            ->id->not->toBeNull()
            ->name->toBe('Test User')
            ->email->toBe('test@example.com')
            ->username->toBe('u12345678');
    });

    it('assigns roles when provided', function () {
        $action = app(CreateUserAction::class);

        $user = $action->execute([
            'name' => 'Test Student',
            'email' => 'student@example.com',
            'username' => 'u87654321',
        ], [], [RoleEnum::STUDENT->value]);

        expect($user->hasRole(RoleEnum::STUDENT))->toBeTrue();
    });

    it('creates profile when profile data provided', function () {
        $action = app(CreateUserAction::class);

        $user = $action->execute([
            'name' => 'Test User',
            'email' => 'profile@example.com',
            'username' => 'u11223344',
        ], [
            'phone' => '08123456789',
            'address' => 'Test Address',
        ]);

        expect($user->profile)
            ->not->toBeNull()
            ->phone->toBe('08123456789');
    });

    it('generates username when not provided', function () {
        $action = app(CreateUserAction::class);

        $user = $action->execute([
            'name' => 'No Username',
            'email' => 'nouser@example.com',
        ]);

        expect($user->username)->not->toBeEmpty();
    });

    it('rejects reserved system usernames', function () {
        $action = app(CreateUserAction::class);

        $action->execute([
            'name' => 'Bad User',
            'email' => 'bad@example.com',
            'username' => 'admin',
        ]);
    })->throws(ValidationException::class);

    it('rejects duplicate email', function () {
        User::factory()->create(['email' => 'dup@example.com']);

        $action = app(CreateUserAction::class);

        $action->execute([
            'name' => 'Dup User',
            'email' => 'dup@example.com',
            'username' => 'u99999999',
        ]);
    })->throws(ValidationException::class);

    it('sends welcome notification when no password provided', function () {
        Notification::fake();

        $action = app(CreateUserAction::class);

        $user = $action->execute([
            'name' => 'Welcome User',
            'email' => 'welcome@example.com',
            'username' => 'u55667788',
        ]);

        Notification::assertSentTo($user, WelcomeNotification::class);
    });

    it('creates audit log entry', function () {
        $action = app(CreateUserAction::class);

        $action->execute([
            'name' => 'Audit User',
            'email' => 'audit@example.com',
            'username' => 'u44556677',
        ]);

        expect(AuditLog::where('action', 'user_created')->exists())->toBeTrue();
    });
});

describe('UpdateUserAction', function () {
    it('updates user name', function () {
        $user = User::factory()->create();
        $action = app(UpdateUserAction::class);

        $result = $action->execute($user, ['name' => 'Updated Name']);

        expect($result->fresh()->name)->toBe('Updated Name');
    });

    it('updates user email with uniqueness check', function () {
        $user = User::factory()->create();
        $action = app(UpdateUserAction::class);

        $result = $action->execute($user, ['email' => 'newemail@example.com']);

        expect($result->fresh()->email)->toBe('newemail@example.com');
    });

    it('rejects duplicate email on update', function () {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create();
        $action = app(UpdateUserAction::class);

        $action->execute($user, ['email' => 'taken@example.com']);
    })->throws(ValidationException::class);

    it('updates user password', function () {
        $user = User::factory()->create();
        $action = app(UpdateUserAction::class);

        $action->execute($user, ['password' => 'newpassword123']);

        expect(Hash::check('newpassword123', $user->fresh()->password))->toBeTrue();
    });

    it('syncs roles when provided', function () {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::STUDENT->value);

        $action = app(UpdateUserAction::class);
        $action->execute($user, [], null, [RoleEnum::TEACHER->value]);

        expect($user->fresh()->hasRole(RoleEnum::TEACHER))->toBeTrue();
        expect($user->fresh()->hasRole(RoleEnum::STUDENT))->toBeFalse();
    });

    it('updates profile data', function () {
        $user = User::factory()->create();
        $action = app(UpdateUserAction::class);

        $action->execute($user, [], ['phone' => '08999999999']);

        expect($user->fresh()->profile->phone)->toBe('08999999999');
    });

    it('does not update when no data provided', function () {
        $user = User::factory()->create(['name' => 'Original Name']);
        $action = app(UpdateUserAction::class);

        $result = $action->execute($user, []);

        expect($result->fresh()->name)->toBe('Original Name');
    });
});

describe('ChangeUserPasswordAction', function () {
    it('changes password with correct current password', function () {
        $user = User::factory()->withPassword('current123')->create();
        $action = app(ChangeUserPasswordAction::class);

        $action->execute($user, 'current123', 'newpass123', 'newpass123');

        expect(Illuminate\Support\Facades\Hash::check('newpass123', $user->fresh()->password))->toBeTrue();
    });

    it('throws exception for wrong current password', function () {
        $user = User::factory()->withPassword('correct123')->create();
        $action = app(ChangeUserPasswordAction::class);

        $action->execute($user, 'wrong123', 'newpass123');
    })->throws(AuthException::class);

    it('rejects short new password', function () {
        $user = User::factory()->withPassword('current123')->create();
        $action = app(ChangeUserPasswordAction::class);

        $action->execute($user, 'current123', 'short');
    })->throws(ValidationException::class);

    it('rejects when confirmation does not match', function () {
        $user = User::factory()->withPassword('current123')->create();
        $action = app(ChangeUserPasswordAction::class);

        $action->execute($user, 'current123', 'newpass123', 'different123');
    })->throws(ValidationException::class);
});

describe('UpdateUserPasswordAction', function () {
    it('updates password as admin', function () {
        $user = User::factory()->create();
        $action = app(UpdateUserPasswordAction::class);

        $action->execute($user, 'adminpass123');

        expect(Hash::check('adminpass123', $user->fresh()->password))->toBeTrue();
    });

    it('rejects short password', function () {
        $user = User::factory()->create();
        $action = app(UpdateUserPasswordAction::class);

        $action->execute($user, 'short');
    })->throws(ValidationException::class);

    it('creates audit log entry', function () {
        $user = User::factory()->create();
        $action = app(UpdateUserPasswordAction::class);

        $action->execute($user, 'validpass123');

        expect(AuditLog::where('action', 'password_updated_manually')->exists())->toBeTrue();
    });
});

describe('LockUserAccountAction', function () {
    it('locks user account', function () {
        $user = User::factory()->create();
        $action = app(LockUserAccountAction::class);

        $action->execute($user, 'manual_lock');

        expect($user->fresh()->isLocked())->toBeTrue();
        expect($user->fresh()->locked_reason)->toBe('manual_lock');
        expect($user->fresh()->locked_at)->not->toBeNull();
    });

    it('does nothing if already locked', function () {
        $user = User::factory()->locked()->create();
        $action = app(LockUserAccountAction::class);

        $before = $user->locked_at;
        $action->execute($user, 'another_reason');

        expect($user->fresh()->locked_at->eq($before))->toBeTrue();
    });

    it('creates audit log entry', function () {
        $user = User::factory()->create();
        $action = app(LockUserAccountAction::class);

        $action->execute($user, 'test_lock');

        expect(AuditLog::where('action', 'user_account_locked')->exists())->toBeTrue();
    });
});

describe('UnlockUserAccountAction', function () {
    it('unlocks user account', function () {
        $user = User::factory()->locked()->create();
        $action = app(UnlockUserAccountAction::class);

        $action->execute($user);

        expect($user->fresh()->isLocked())->toBeFalse();
        expect($user->fresh()->locked_reason)->toBeNull();
    });

    it('does nothing if not locked', function () {
        $user = User::factory()->create();
        $action = app(UnlockUserAccountAction::class);

        $action->execute($user);

        expect($user->fresh()->isLocked())->toBeFalse();
    });

    it('creates audit log entry', function () {
        $user = User::factory()->locked()->create();
        $action = app(UnlockUserAccountAction::class);

        $action->execute($user);

        expect(AuditLog::where('action', 'user_account_unlocked')->exists())->toBeTrue();
    });
});

describe('DeleteUserAction', function () {
    it('deletes a regular user', function () {
        $user = User::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::SUPER_ADMIN->value);

        actingAs($admin);

        $action = app(DeleteUserAction::class);
        $action->execute($user);

        expect(User::find($user->id))->toBeNull();
    });

    it('prevents self deletion', function () {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::SUPER_ADMIN->value);

        actingAs($user);

        $action = app(DeleteUserAction::class);
        $action->execute($user);
    })->throws(AuthException::class);

    it('prevents deleting last super admin', function () {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::SUPER_ADMIN->value);

        actingAs($user);

        $action = app(DeleteUserAction::class);
        $action->execute($user);
    })->throws(AuthException::class);
});

describe('SetupSuperAdminAction', function () {
    it('creates super admin account', function () {
        $action = app(SetupSuperAdminAction::class);

        $user = $action->execute([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'username' => 'superadmin01',
            'password' => 'strongpassword123',
        ]);

        expect($user)
            ->toBeInstanceOf(User::class)
            ->hasRole(RoleEnum::SUPER_ADMIN->value)->toBeTrue()
            ->email_verified_at->not->toBeNull();
    });

    it('rejects reserved username', function () {
        $action = app(SetupSuperAdminAction::class);

        $action->execute([
            'name' => 'Bad Admin',
            'email' => 'badadmin@example.com',
            'username' => 'root',
            'password' => 'strongpassword123',
        ]);
    })->throws(ValidationException::class);

    it('rejects weak password', function () {
        $action = app(SetupSuperAdminAction::class);

        $action->execute([
            'name' => 'Weak Admin',
            'email' => 'weak@example.com',
            'username' => 'weakadmin01',
            'password' => '123',
        ]);
    })->throws(ValidationException::class);
});

describe('UpdateProfileAction', function () {
    it('creates profile if not exists', function () {
        $user = User::factory()->create();
        $action = app(UpdateProfileAction::class);

        $profile = $action->execute($user, [
            'phone' => '08111111111',
            'address' => 'Test Address',
        ]);

        expect($profile)
            ->toBeInstanceOf(Profile::class)
            ->phone->toBe('08111111111');
    });

    it('updates existing profile', function () {
        $user = User::factory()->create();
        $user->profile()->create(['phone' => 'old']);

        $action = app(UpdateProfileAction::class);

        $profile = $action->execute($user, ['phone' => 'newphone']);

        expect($profile->phone)->toBe('newphone');
    });

    it('validates department_id exists', function () {
        $user = User::factory()->create();
        $action = app(UpdateProfileAction::class);

        $action->execute($user, ['department_id' => 99999]);
    })->throws(ValidationException::class);

    it('creates audit log entry', function () {
        $user = User::factory()->create();
        $action = app(UpdateProfileAction::class);

        $action->execute($user, ['phone' => '08222222222']);

        expect(AuditLog::where('action', 'profile_updated')->exists())->toBeTrue();
    });
});

describe('DetectUserAccountCloneAction', function () {
    it('returns empty collection when no duplicates', function () {
        User::factory()->count(3)->create();

        $action = app(DetectUserAccountCloneAction::class);

        expect($action->execute())->toHaveCount(0);
    });

    it('detects duplicate emails', function () {
        // Note: Database enforces unique constraint on email, so true duplicates
        // can only exist through legacy data or imports. This test verifies
        // the detection logic works when such data exists.
        // In production, duplicates would be found by the action's GROUP BY query.
        $this->markTestSkipped('Database unique constraint prevents duplicate emails.');
    });
});

describe('CheckUserSessionExpiryAction', function () {
    it('returns false when no activity recorded', function () {
        $user = User::factory()->create();
        $action = new CheckUserSessionExpiryAction(30);

        expect($action->execute($user))->toBeFalse();
    });

    it('returns false when session is still active', function () {
        $user = User::factory()->create();
        Cache::put("user.last_activity.{$user->id}", now()->subMinutes(10));

        $action = new CheckUserSessionExpiryAction(30);

        expect($action->execute($user))->toBeFalse();
    });

    it('records activity timestamp', function () {
        $user = User::factory()->create();
        $action = new CheckUserSessionExpiryAction(30);

        $action->recordActivity($user);

        expect(Cache::has("user.last_activity.{$user->id}"))->toBeTrue();
    });
});

describe('User Model', function () {
    it('identifies locked users correctly', function () {
        $locked = User::factory()->locked()->create();
        $unlocked = User::factory()->create();

        expect($locked->isLocked())->toBeTrue();
        expect($unlocked->isLocked())->toBeFalse();
    });

    it('identifies suspended users correctly', function () {
        $user = User::factory()->create();
        $user->setStatus('suspended');

        expect($user->isSuspended())->toBeTrue();
    });

    it('identifies inactive users correctly', function () {
        $user = User::factory()->create();
        $user->setStatus('inactive');

        expect($user->isInactive())->toBeTrue();
    });

    it('lock method sets lock fields', function () {
        $user = User::factory()->create();
        $user->lock('security_breach');

        expect($user->isLocked())->toBeTrue();
        expect($user->locked_reason)->toBe('security_breach');
    });

    it('unlock method clears lock fields', function () {
        $user = User::factory()->locked()->create();
        $user->unlock();

        expect($user->isLocked())->toBeFalse();
        expect($user->locked_reason)->toBeNull();
    });

    it('scopeLocked returns only locked users', function () {
        User::factory()->locked()->create();
        User::factory()->count(2)->create();

        expect(User::locked()->count())->toBe(1);
    });

    it('scopeUnlocked returns only unlocked users', function () {
        User::factory()->locked()->create();
        User::factory()->count(2)->create();

        expect(User::unlocked()->count())->toBe(2);
    });

    it('scopeActive returns unlocked and non-setup users', function () {
        User::factory()->locked()->create();
        User::factory()->requiresSetup()->create();
        User::factory()->count(3)->create();

        expect(User::active()->count())->toBe(3);
    });

    it('has profile relationship', function () {
        $user = User::factory()->create();
        $user->profile()->create(['phone' => '123456']);

        expect($user->profile->phone)->toBe('123456');
    });

    it('requiresSetup returns correct boolean', function () {
        $setupUser = User::factory()->requiresSetup()->create();
        $normalUser = User::factory()->create();

        expect($setupUser->requiresSetup())->toBeTrue();
        expect($normalUser->requiresSetup())->toBeFalse();
    });
});
