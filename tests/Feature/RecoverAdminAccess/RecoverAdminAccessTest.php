<?php

declare(strict_types=1);

use App\Actions\Setup\RecoverAdminAccessAction;
use App\Enums\Auth\AccountStatus;
use App\Models\User;
use App\Notifications\Auth\AdminRecoveredNotification;
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

    it('creates a new admin user with a generated username', function () {
        $user = app(RecoverAdminAccessAction::class)->execute(
            email: 'admin@internara.test',
            password: 'secure-password',
        );

        expect($user)->toBeInstanceOf(User::class);
        expect($user->email)->toBe('admin@internara.test');
        expect($user->name)->toBe('Recovery Admin');
        expect($user->username)->toStartWith('admin_');
    });

    it('creates a profile for the new user', function () {
        $user = app(RecoverAdminAccessAction::class)->execute(
            email: 'admin@internara.test',
            password: 'secure-password',
        );

        assertDatabaseHas('profiles', ['user_id' => $user->id]);
        expect($user->relationLoaded('profile'))->toBeFalse();
        expect($user->profile)->not->toBeNull();
    });

    it('assigns the PROTECTED status to the recovery admin', function () {
        $user = app(RecoverAdminAccessAction::class)->execute(
            email: 'admin@internara.test',
            password: 'secure-password',
        );

        expect($user->latestStatus()->name)->toBe(AccountStatus::PROTECTED->value);
    });

    it('assigns the super_admin role by default', function () {
        $user = app(RecoverAdminAccessAction::class)->execute(
            email: 'admin@internara.test',
            password: 'secure-password',
        );

        expect($user->hasRole('super_admin'))->toBeTrue();
    });

    it('hashes the password store', function () {
        $password = 'secure-password';

        $user = app(RecoverAdminAccessAction::class)->execute(
            email: 'admin@internara.test',
            password: $password,
        );

        expect($user->password)->not->toBe($password);
        expect(Hash::check($password, $user->password))->toBeTrue();
    });

    it('logs an audit entry with masked PII', function () {
        $user = app(RecoverAdminAccessAction::class)->execute(
            email: 'admin@internara.test',
            password: 'secure-password',
        );

        $activity = Activity::where('subject_id', $user->id)
            ->where('subject_type', User::class)
            ->first();

        expect($activity)->not->toBeNull();
        expect($activity->description)->toBe('admin_recovered');
        expect($activity->log_name)->toBe('Setup');
        expect($activity->subject_type)->toBe(User::class);
        expect($activity->subject_id)->toBe($user->id);
        expect($activity->properties->get('payload')['type'])->toBe('create');
        expect($activity->properties->get('payload')['email'])->toContain('***');
        expect($activity->properties->get('payload')['email'])->not->toBe('admin@internara.test');
        expect($activity->properties->get('payload')['role'])->toBe('super_admin');
    });

    it('does not log a causer for CLI-initiated recovery', function () {
        $user = app(RecoverAdminAccessAction::class)->execute(
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

        $user = app(RecoverAdminAccessAction::class)->execute(
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

        $user = app(RecoverAdminAccessAction::class)->execute(
            email: $existing->email,
            password: 'new-password',
            isReset: true,
        );

        expect($user->latestStatus()->name)->toBe(AccountStatus::VERIFIED->value);
    });

    it('syncs the super_admin role on reset', function () {
        $existing = User::factory()->create();
        $existing->assignRole('super_admin');

        $user = app(RecoverAdminAccessAction::class)->execute(
            email: $existing->email,
            password: 'new-password',
            isReset: true,
        );

        expect($user->hasRole('super_admin'))->toBeTrue();
    });

    it('does not create a new profile on reset', function () {
        $existing = User::factory()->create();

        app(RecoverAdminAccessAction::class)->execute(
            email: $existing->email,
            password: 'new-password',
            isReset: true,
        );

        expect($existing->fresh()->profile)->toBeNull();
    });

    it('does not change the username on reset', function () {
        $existing = User::factory()->create(['username' => 'original_admin']);

        $user = app(RecoverAdminAccessAction::class)->execute(
            email: $existing->email,
            password: 'new-password',
            isReset: true,
        );

        expect($user->username)->toBe('original_admin');
    });

    it('logs an audit entry with type reset', function () {
        $existing = User::factory()->create();
        $existing->setStatus(AccountStatus::SUSPENDED);

        $user = app(RecoverAdminAccessAction::class)->execute(
            email: $existing->email,
            password: 'new-password',
            isReset: true,
        );

        $activity = Activity::where('subject_id', $user->id)
            ->where('subject_type', User::class)
            ->orderByDesc('id')
            ->first();

        expect($activity)->not->toBeNull();
        expect($activity->description)->toBe('admin_recovered');
        expect($activity->properties->get('payload')['type'])->toBe('reset');
        expect($activity->properties->get('payload')['email'])->toContain('***');
        expect($activity->properties->get('payload')['email'])->not->toBe($existing->email);
    });

    it('resets a user in any non-terminal status', function () {
        $existing = User::factory()->create();
        $existing->setStatus(AccountStatus::PROVISIONED);

        $user = app(RecoverAdminAccessAction::class)->execute(
            email: $existing->email,
            password: 'new-password',
            isReset: true,
        );

        expect($user->latestStatus()->name)->toBe(AccountStatus::VERIFIED->value);
    });

});

describe('logging and PII masking', function () {

    it('masks email in audit log payload', function () {
        $user = app(RecoverAdminAccessAction::class)->execute(
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

    it('writes a system log entry via SmartLogger on create', function () {
        Log::spy();

        app(RecoverAdminAccessAction::class)->execute(
            email: 'admin@internara.test',
            password: 'secure-password',
        );

        Log::shouldHaveReceived('info')
            ->withArgs(fn ($msg) => str_contains($msg, 'admin_recovery_'));
    });

    it('writes a system log entry via SmartLogger on reset', function () {
        Log::spy();
        $existing = User::factory()->create();

        app(RecoverAdminAccessAction::class)->execute(
            email: $existing->email,
            password: 'new-password',
            isReset: true,
        );

        Log::shouldHaveReceived('info')
            ->withArgs(fn ($msg) => str_contains($msg, 'admin_recovery_'));
    });

    it('includes hostname and server_ip in audit payload', function () {
        $user = app(RecoverAdminAccessAction::class)->execute(
            email: 'metadata@internara.test',
            password: 'secure-password',
        );

        $activity = Activity::where('subject_id', $user->id)
            ->where('subject_type', User::class)
            ->first();

        $payload = $activity->properties->get('payload');
        expect($payload)->toHaveKey('hostname');
        expect($payload)->toHaveKey('server_ip');
        expect($payload['hostname'])->toBe(gethostname());
    });

});

describe('session invalidation', function () {

    it('regenerates remember_token on create', function () {
        $user = app(RecoverAdminAccessAction::class)->execute(
            email: 'session-test@internara.test',
            password: 'secure-password',
        );

        expect($user->remember_token)->not->toBeNull();
    });

    it('regenerates remember_token on reset', function () {
        $existing = User::factory()->create(['remember_token' => 'old-token-value']);
        $existing->setStatus(AccountStatus::SUSPENDED);

        $user = app(RecoverAdminAccessAction::class)->execute(
            email: $existing->email,
            password: 'new-password',
            isReset: true,
        );

        expect($user->remember_token)->not->toBe('old-token-value');
    });

});

describe('admin notification', function () {

    it('sends AdminRecoveredNotification to existing admin users on create', function () {
        Notification::fake();

        $existingAdmin = User::factory()->create();
        $existingAdmin->assignRole('super_admin');

        app(RecoverAdminAccessAction::class)->execute(
            email: 'newadmin@internara.test',
            password: 'secure-password',
        );

        Notification::assertSentTo(
            $existingAdmin,
            AdminRecoveredNotification::class,
        );
    });

    it('sends AdminRecoveredNotification to existing admin users on reset', function () {
        Notification::fake();

        $existingAdmin = User::factory()->create();
        $existingAdmin->assignRole('super_admin');

        $target = User::factory()->create();
        $target->setStatus(AccountStatus::SUSPENDED);

        app(RecoverAdminAccessAction::class)->execute(
            email: $target->email,
            password: 'new-password',
            isReset: true,
        );

        Notification::assertSentTo(
            $existingAdmin,
            AdminRecoveredNotification::class,
        );
    });

    it('does not send notification when no other admins exist', function () {
        Notification::fake();

        app(RecoverAdminAccessAction::class)->execute(
            email: 'lonely-admin@internara.test',
            password: 'secure-password',
        );

        Notification::assertNothingSent();
    });

    it('does not send notification to the recovered user', function () {
        Notification::fake();

        $existingAdmin = User::factory()->create();
        $existingAdmin->assignRole('super_admin');

        app(RecoverAdminAccessAction::class)->execute(
            email: $existingAdmin->email,
            password: 'new-password',
            isReset: true,
        );

        Notification::assertNotSentTo(
            $existingAdmin,
            AdminRecoveredNotification::class,
        );
    });

    it('notifies admin role users as well as super_admin', function () {
        Notification::fake();

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        app(RecoverAdminAccessAction::class)->execute(
            email: 'another-admin@internara.test',
            password: 'secure-password',
        );

        Notification::assertSentTo($superAdmin, AdminRecoveredNotification::class);
        Notification::assertSentTo($admin, AdminRecoveredNotification::class);
    });

});

describe('validation and edge cases', function () {

    it('throws ModelNotFoundException when resetting a non-existent user', function () {
        app(RecoverAdminAccessAction::class)->execute(
            email: 'nonexistent@test.com',
            password: 'password',
            isReset: true,
        );
    })->throws(ModelNotFoundException::class);

    it('assigns a custom role instead of default super_admin', function () {
        $user = app(RecoverAdminAccessAction::class)->execute(
            email: 'custom-role@internara.test',
            password: 'password',
            role: 'admin',
        );

        expect($user->hasRole('super_admin'))->toBeFalse();
        expect($user->hasRole('admin'))->toBeTrue();
        expect($user->latestStatus()->name)->toBe(AccountStatus::PROTECTED->value);
    });

    it('assigns custom role in reset mode', function () {
        $existing = User::factory()->create();
        $existing->setStatus(AccountStatus::SUSPENDED);

        $user = app(RecoverAdminAccessAction::class)->execute(
            email: $existing->email,
            password: 'new-password',
            isReset: true,
            role: 'admin',
        );

        expect($user->hasRole('super_admin'))->toBeFalse();
        expect($user->hasRole('admin'))->toBeTrue();
    });

    it('persists the user to the database', function () {
        app(RecoverAdminAccessAction::class)->execute(
            email: 'persist@internara.test',
            password: 'secure-password',
        );

        assertDatabaseHas('users', ['email' => 'persist@internara.test']);
    });

});
