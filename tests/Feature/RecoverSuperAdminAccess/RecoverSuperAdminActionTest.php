<?php

declare(strict_types=1);

use App\Actions\Setup\RecoverSuperAdminAction;
use App\Enums\Auth\AccountStatus;
use App\Models\User;
use App\Notifications\Auth\SuperAdminRecoveredNotification;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
});

describe('create mode (isReset=false)', function () {

    it('creates a new super admin user with a generated username', function () {
        $user = app(RecoverSuperAdminAction::class)->execute(
            email: 'admin@internara.test',
            password: 'secure-password',
        );

        expect($user)->toBeInstanceOf(User::class);
        expect($user->email)->toBe('admin@internara.test');
        expect($user->name)->toBe('Recovery Admin');
        expect($user->username)->toStartWith('admin_');
    });

    it('creates a profile for the new user', function () {
        $user = app(RecoverSuperAdminAction::class)->execute(
            email: 'admin@internara.test',
            password: 'secure-password',
        );

        assertDatabaseHas('profiles', ['user_id' => $user->id]);
        expect($user->relationLoaded('profile'))->toBeFalse();
        expect($user->profile)->not->toBeNull();
    });

    it('assigns the PROTECTED status to the recovery admin', function () {
        $user = app(RecoverSuperAdminAction::class)->execute(
            email: 'admin@internara.test',
            password: 'secure-password',
        );

        expect($user->latestStatus()->name)->toBe(AccountStatus::PROTECTED->value);
    });

    it('assigns the super_admin role', function () {
        $user = app(RecoverSuperAdminAction::class)->execute(
            email: 'admin@internara.test',
            password: 'secure-password',
        );

        expect($user->hasRole('super_admin'))->toBeTrue();
    });

    it('hashes the password', function () {
        $password = 'secure-password';

        $user = app(RecoverSuperAdminAction::class)->execute(
            email: 'admin@internara.test',
            password: $password,
        );

        expect($user->password)->not->toBe($password);
        expect(Hash::check($password, $user->password))->toBeTrue();
    });

    it('logs an audit entry with masked PII', function () {
        $user = app(RecoverSuperAdminAction::class)->execute(
            email: 'admin@internara.test',
            password: 'secure-password',
        );

        $activity = Activity::where('subject_id', $user->id)
            ->where('subject_type', User::class)
            ->first();

        expect($activity)->not->toBeNull();
        expect($activity->description)->toBe('super_admin_recovered');
        expect($activity->properties->get('payload')['type'])->toBe('create');
        expect($activity->properties->get('payload')['email'])->toContain('***');
        expect($activity->properties->get('payload')['email'])->not->toBe('admin@internara.test');
    });

    it('does not log a causer for CLI-initiated recovery', function () {
        $user = app(RecoverSuperAdminAction::class)->execute(
            email: 'admin@internara.test',
            password: 'secure-password',
        );

        $activity = Activity::where('subject_id', $user->id)
            ->where('subject_type', User::class)
            ->first();

        expect($activity->causer_id)->toBeNull();
        expect($activity->causer_type)->toBeNull();
    });

});

describe('reset mode (isReset=true)', function () {

    it('resets the password and clears the lock', function () {
        $existing = User::factory()
            ->withPassword('old-password')
            ->create(['locked_at' => now(), 'locked_reason' => 'manual_lock']);
        $existing->setStatus(AccountStatus::SUSPENDED);

        $user = app(RecoverSuperAdminAction::class)->execute(
            email: $existing->email,
            password: 'new-secure-password',
            isReset: true,
        );

        expect($user->id)->toBe($existing->id);
        expect($user->locked_at)->toBeNull();
        expect($user->locked_reason)->toBeNull();
        expect(Hash::check('new-secure-password', $user->password))->toBeTrue();
        expect(Hash::check('old-password', $user->password))->toBeFalse();
    });

    it('sets the status to VERIFIED after reset', function () {
        $existing = User::factory()->create();
        $existing->setStatus(AccountStatus::SUSPENDED);

        $user = app(RecoverSuperAdminAction::class)->execute(
            email: $existing->email,
            password: 'new-password',
            isReset: true,
        );

        expect($user->latestStatus()->name)->toBe(AccountStatus::VERIFIED->value);
    });

    it('syncs the super_admin role on reset', function () {
        $existing = User::factory()->create();
        $existing->assignRole('super_admin');

        $user = app(RecoverSuperAdminAction::class)->execute(
            email: $existing->email,
            password: 'new-password',
            isReset: true,
        );

        expect($user->hasRole('super_admin'))->toBeTrue();
    });

    it('does not create a new profile on reset', function () {
        $existing = User::factory()->create();

        app(RecoverSuperAdminAction::class)->execute(
            email: $existing->email,
            password: 'new-password',
            isReset: true,
        );

        expect($existing->fresh()->profile)->toBeNull();
    });

    it('does not change the username on reset', function () {
        $existing = User::factory()->create(['username' => 'original_admin']);

        $user = app(RecoverSuperAdminAction::class)->execute(
            email: $existing->email,
            password: 'new-password',
            isReset: true,
        );

        expect($user->username)->toBe('original_admin');
    });

    it('logs an audit entry with type reset', function () {
        $existing = User::factory()->create();
        $existing->setStatus(AccountStatus::SUSPENDED);

        $user = app(RecoverSuperAdminAction::class)->execute(
            email: $existing->email,
            password: 'new-password',
            isReset: true,
        );

        $activity = Activity::where('subject_id', $user->id)
            ->where('subject_type', User::class)
            ->orderByDesc('id')
            ->first();

        expect($activity)->not->toBeNull();
        expect($activity->description)->toBe('super_admin_recovered');
        expect($activity->properties->get('payload')['type'])->toBe('reset');
        expect($activity->properties->get('payload')['email'])->toContain('***');
        expect($activity->properties->get('payload')['email'])->not->toBe($existing->email);
    });

    it('resets a user in any non-terminal status', function () {
        $existing = User::factory()->create();
        $existing->setStatus(AccountStatus::PROVISIONED);

        $user = app(RecoverSuperAdminAction::class)->execute(
            email: $existing->email,
            password: 'new-password',
            isReset: true,
        );

        expect($user->latestStatus()->name)->toBe(AccountStatus::VERIFIED->value);
    });

});

describe('logging and PII masking', function () {

    it('masks email in audit log payload', function () {
        $user = app(RecoverSuperAdminAction::class)->execute(
            email: 'john.doe@internara.test',
            password: 'secure-password',
        );

        $activity = Activity::where('subject_id', $user->id)
            ->where('subject_type', User::class)
            ->first();

        $masked = $activity->properties->get('payload')['email'];
        expect($masked)->not->toBe('john.doe@internara.test');
        expect($masked)->toStartWith('jo***');
        expect($masked)->toContain('***@');
    });

    it('writes a system log entry on create', function () {
        Log::spy();

        app(RecoverSuperAdminAction::class)->execute(
            email: 'admin@internara.test',
            password: 'secure-password',
        );

        Log::shouldHaveReceived('info')
            ->withArgs(fn ($msg) => str_contains($msg, 'super_admin_recovery_'));
    });

    it('writes a system log entry on reset', function () {
        Log::spy();
        $existing = User::factory()->create();

        app(RecoverSuperAdminAction::class)->execute(
            email: $existing->email,
            password: 'new-password',
            isReset: true,
        );

        Log::shouldHaveReceived('info')
            ->withArgs(fn ($msg) => str_contains($msg, 'super_admin_recovery_'));
    });

});

describe('session invalidation', function () {

    it('regenerates remember_token on create', function () {
        $user = app(RecoverSuperAdminAction::class)->execute(
            email: 'session-test@internara.test',
            password: 'secure-password',
        );

        expect($user->remember_token)->not->toBeNull();
    });

    it('regenerates remember_token on reset', function () {
        $existing = User::factory()->create(['remember_token' => 'old-token-value']);
        $existing->setStatus(AccountStatus::SUSPENDED);

        $user = app(RecoverSuperAdminAction::class)->execute(
            email: $existing->email,
            password: 'new-password',
            isReset: true,
        );

        expect($user->remember_token)->not->toBe('old-token-value');
    });

});

describe('admin notification', function () {

    it('sends SuperAdminRecoveredNotification to existing super admins on create', function () {
        Notification::fake();

        $existingAdmin = User::factory()->create();
        $existingAdmin->assignRole('super_admin');

        app(RecoverSuperAdminAction::class)->execute(
            email: 'newadmin@internara.test',
            password: 'secure-password',
        );

        Notification::assertSentTo(
            $existingAdmin,
            SuperAdminRecoveredNotification::class,
        );
    });

    it('sends SuperAdminRecoveredNotification to existing super admins on reset', function () {
        Notification::fake();

        $existingAdmin = User::factory()->create();
        $existingAdmin->assignRole('super_admin');

        $target = User::factory()->create();
        $target->setStatus(AccountStatus::SUSPENDED);

        app(RecoverSuperAdminAction::class)->execute(
            email: $target->email,
            password: 'new-password',
            isReset: true,
        );

        Notification::assertSentTo(
            $existingAdmin,
            SuperAdminRecoveredNotification::class,
        );
    });

    it('does not send notification when no other super admins exist', function () {
        Notification::fake();

        app(RecoverSuperAdminAction::class)->execute(
            email: 'lonely-admin@internara.test',
            password: 'secure-password',
        );

        Notification::assertNothingSent();
    });

    it('does not send notification to the recovered user', function () {
        Notification::fake();

        $existingAdmin = User::factory()->create();
        $existingAdmin->assignRole('super_admin');

        app(RecoverSuperAdminAction::class)->execute(
            email: $existingAdmin->email,
            password: 'new-password',
            isReset: true,
        );

        Notification::assertNotSentTo(
            $existingAdmin,
            SuperAdminRecoveredNotification::class,
        );
    });

    it('does not notify regular admin role users', function () {
        Notification::fake();

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        app(RecoverSuperAdminAction::class)->execute(
            email: 'another-admin@internara.test',
            password: 'secure-password',
        );

        Notification::assertNothingSent();
    });

});

describe('validation and edge cases', function () {

    it('throws ModelNotFoundException when resetting a non-existent user', function () {
        app(RecoverSuperAdminAction::class)->execute(
            email: 'nonexistent@test.com',
            password: 'password',
            isReset: true,
        );
    })->throws(ModelNotFoundException::class);

    it('persists the user to the database', function () {
        app(RecoverSuperAdminAction::class)->execute(
            email: 'persist@internara.test',
            password: 'secure-password',
        );

        assertDatabaseHas('users', ['email' => 'persist@internara.test']);
    });

});
