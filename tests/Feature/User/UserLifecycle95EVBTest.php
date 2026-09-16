<?php

declare(strict_types=1);

use App\Modules\Academic\Domain\Department\Models\Department;
use App\Modules\Auth\Domain\AccessToken\Models\AccessToken;
use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Partner\Domain\Company\Models\Company;
use App\Modules\User\Domain\AccountStatus\Notifications\AccountStatusNotification;
use App\Modules\User\Domain\Notify\WelcomeNotification;
use App\Modules\User\Domain\Profile\Actions\UpdateProfileAction;
use App\Modules\User\Domain\Profile\Data\UpdateProfileData;
use App\Modules\User\Domain\Profile\Events\ProfileUpdated;
use App\Modules\User\Domain\Profile\Models\Profile;
use App\Modules\User\Domain\UserManagement\Actions\ArchiveStudentAccountsAction;
use App\Modules\User\Domain\UserManagement\Actions\BatchDeleteUserAction;
use App\Modules\User\Domain\UserManagement\Actions\CreateUserAction;
use App\Modules\User\Domain\UserManagement\Actions\DeleteUserAction;
use App\Modules\User\Domain\UserManagement\Actions\RevokeUserActivationTokensAction;
use App\Modules\User\Domain\UserManagement\Actions\SetUserStatusAction;
use App\Modules\User\Domain\UserManagement\Actions\ToggleUserStatusAction;
use App\Modules\User\Domain\UserManagement\Actions\UpdateUserAction;
use App\Modules\User\Domain\UserManagement\Data\CreateUserData;
use App\Modules\User\Domain\UserManagement\Data\SetUserStatusData;
use App\Modules\User\Domain\UserManagement\Data\UpdateUserData;
use App\Modules\User\Domain\UserManagement\Events\UserCreated;
use App\Modules\User\Domain\UserManagement\Events\UserDeleted;
use App\Modules\User\Domain\UserManagement\Events\UserStatusChanged;
use App\Modules\User\Domain\UserManagement\Events\UserUpdated;
use App\Modules\User\Domain\UserManagement\Livewire\UserManager;
use App\Modules\User\Domain\UserManagement\Notifications\ActivationCodeNotification;
use App\Modules\User\Enums\AccountStatus;
use App\Modules\User\Enums\BloodType;
use App\Modules\User\Enums\Gender;
use App\Modules\User\Models\User;
use App\Modules\User\Observers\UserObserver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

describe('95EVB: user lifecycle actions', function (): void {
    test('95EVB-FR-USER-003: create without a password mints a hashed random secret', function (): void {
        $first = app(CreateUserAction::class)->execute(new CreateUserData(
            user: ['name' => 'Mint One', 'email' => 'mint.one@example.com'],
            sendNotification: false,
        ));
        $second = app(CreateUserAction::class)->execute(new CreateUserData(
            user: ['name' => 'Mint Two', 'email' => 'mint.two@example.com'],
            sendNotification: false,
        ));

        $firstHash = $first->fresh()->getAttributes()['password'];
        $secondHash = $second->fresh()->getAttributes()['password'];

        expect($firstHash)->not->toBeEmpty()
            ->and($firstHash)->not->toBe($secondHash)
            ->and(Hash::check('password', $firstHash))->toBeFalse();
    });

    test('95EVB-FR-USER-005: create sends the activation notice and mints an activation token', function (): void {
        Notification::fake();

        $user = app(CreateUserAction::class)->execute(new CreateUserData(
            user: ['name' => 'Activation Notice', 'email' => 'activation.notice@example.com'],
        ));

        Notification::assertSentTo($user, ActivationCodeNotification::class);
        $this->assertDatabaseHas('access_tokens', [
            'user_id' => $user->id,
            'token_type' => 'activation',
        ]);
        expect(AccessToken::verify($user, 'activation', 'wrong-code'))->toBeFalse();
    });

    test('95EVB-FR-USER-006: the welcome note travels only with an auto-generated password', function (): void {
        Notification::fake();

        $minted = app(CreateUserAction::class)->execute(new CreateUserData(
            user: ['name' => 'Minted Secret', 'email' => 'minted.secret@example.com'],
        ));

        Notification::assertSentTo(
            $minted,
            WelcomeNotification::class,
            fn (WelcomeNotification $n): bool => strlen($n->temporaryPassword) === 12
                && Hash::check($n->temporaryPassword, $minted->fresh()->getAttributes()['password']),
        );

        $chosen = app(CreateUserAction::class)->execute(new CreateUserData(
            user: ['name' => 'Chosen Secret', 'email' => 'chosen.secret@example.com', 'password' => 'hand-picked-secret'],
        ));

        Notification::assertNotSentTo($chosen, WelcomeNotification::class);
    });

    test('95EVB-FR-USER-010: batch delete removes the deletable and counts the skipped (also 95EVB-NFR-USER-009, 95EVB-FR-USER-054)', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $first = User::factory()->create();
        $second = User::factory()->create();

        $result = app(BatchDeleteUserAction::class)->execute([
            $first->id,
            $second->id,
            $admin->id,
            $superAdmin->id,
            (string) Str::uuid(),
        ]);

        expect($result)->toBe(['deleted' => 2, 'skipped' => 3]);
        $this->assertDatabaseMissing('users', ['id' => $first->id]);
        $this->assertDatabaseMissing('users', ['id' => $second->id]);
        $this->assertModelExists($admin->fresh());
        $this->assertModelExists($superAdmin->fresh());
    });

    test('95EVB-FR-USER-012: create, update, and delete each dispatch their domain event', function (): void {
        Event::fake();

        $user = app(CreateUserAction::class)->execute(new CreateUserData(
            user: ['name' => 'Event Birth', 'username' => 'eventbirth', 'email' => 'event.birth@example.com', 'password' => 'secret-1'],
            sendNotification: false,
        ));
        Event::assertDispatched(UserCreated::class);

        app(UpdateUserAction::class)->execute(new UpdateUserData(userId: $user->id, user: ['name' => 'Event Renamed']));
        Event::assertDispatched(UserUpdated::class);

        app(DeleteUserAction::class)->execute($user);
        Event::assertDispatched(UserDeleted::class);

        $existing = User::factory()->create();
        expect(fn () => app(CreateUserAction::class)->execute(new CreateUserData(
            user: ['name' => 'No Event', 'username' => 'noeventuser', 'email' => $existing->email, 'password' => 'secret-2'],
            sendNotification: false,
        )))->toThrow(ValidationException::class);
        Event::assertNotDispatched(UserCreated::class, fn (UserCreated $e): bool => $e->user->email === $existing->email);
    });

    test('95EVB-FR-USER-022: the toggle walks only between verified and suspended', function (): void {
        $user = User::factory()->create(['status' => 'verified']);

        app(ToggleUserStatusAction::class)->execute($user->fresh());
        expect($user->fresh()->status)->toBe(AccountStatus::SUSPENDED);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'suspended']);

        app(ToggleUserStatusAction::class)->execute($user->fresh());
        expect($user->fresh()->status)->toBe(AccountStatus::VERIFIED);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'verified']);
    });

    test('95EVB-FR-USER-023: every applied status change emits its event and notice (also 95EVB-FR-USER-024)', function (): void {
        Event::fake();
        Notification::fake();

        $user = User::factory()->create(['status' => 'activated']);

        app(SetUserStatusAction::class)->execute(new SetUserStatusData(
            userId: $user->id,
            newStatus: AccountStatus::VERIFIED,
            reason: 'verified in test',
            skipAuthCheck: true,
        ));

        Event::assertDispatched(UserStatusChanged::class);
        Notification::assertSentTo(
            $user->fresh(),
            AccountStatusNotification::class,
            fn (AccountStatusNotification $n): bool => $n->status === 'verified' && $n->reason === 'verified in test',
        );
        $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'verified']);
    });

    test('95EVB-FR-USER-030: profile writes upsert and announce themselves (also 95EVB-FR-USER-031)', function (): void {
        Event::fake();
        $user = User::factory()->create();
        expect($user->profile()->exists())->toBeFalse();

        $profile = app(UpdateProfileAction::class)->execute(new UpdateProfileData(
            userId: $user->id,
            profile: ['phone' => '0811000001', 'address' => 'Jl Upsert 1', 'bio' => 'first bio'],
        ));

        expect($profile)->toBeInstanceOf(Profile::class);
        $this->assertDatabaseHas('profiles', ['user_id' => $user->id, 'phone' => '0811000001']);

        app(UpdateProfileAction::class)->execute(new UpdateProfileData(
            userId: $user->id,
            profile: ['phone' => '0811000002'],
        ));

        expect(Profile::where('user_id', $user->id)->count())->toBe(1);
        $this->assertDatabaseHas('profiles', ['user_id' => $user->id, 'phone' => '0811000002']);
        Event::assertDispatched(ProfileUpdated::class);
    });

    test('95EVB-FR-USER-027: one profile links user, department, and company (also 95EVB-FR-USER-028)', function (): void {
        $user = User::factory()->create();

        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'gender' => Gender::MALE,
            'blood_type' => BloodType::O,
            'dob' => '2005-01-15',
            'emergency_contact' => ['name' => 'Ibu', 'phone' => '0811'],
            'company_id' => Company::factory()->create()->id,
        ])->fresh();

        expect($profile->user)->toBeInstanceOf(User::class)
            ->and($profile->user->id)->toBe($user->id)
            ->and($profile->department)->toBeInstanceOf(Department::class)
            ->and($profile->company)->toBeInstanceOf(Company::class)
            ->and($profile->gender)->toBe(Gender::MALE)
            ->and($profile->blood_type)->toBe(BloodType::O)
            ->and($profile->dob)->toBeInstanceOf(Carbon::class)
            ->and($profile->emergency_contact)->toBeArray();
    });

    test('95EVB-FR-USER-029: the profile allowlist lives in a Fillable attribute', function (): void {
        $attributes = (new ReflectionClass(Profile::class))->getAttributes(Fillable::class);

        expect($attributes)->toHaveCount(1);

        $allowed = $attributes[0]->newInstance()->columns;

        foreach (['user_id', 'phone', 'address', 'bio', 'gender', 'blood_type', 'department_id', 'company_id'] as $key) {
            expect($allowed)->toContain($key);
        }
    });

    test('95EVB-FR-USER-046: deleting the super admin is refused at observer, model, and action layers (also 95EVB-FR-USER-047, 95EVB-FR-USER-048)', function (): void {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $regular = User::factory()->create();

        expect(fn () => (new UserObserver)->deleting($regular))->not->toThrow(Throwable::class);
        expect(fn () => (new UserObserver)->deleting($superAdmin))->toThrow(RejectedException::class);

        expect(fn () => $superAdmin->delete())->toThrow(RejectedException::class);
        $this->assertModelExists($superAdmin->fresh());

        expect(fn () => app(DeleteUserAction::class)->execute($superAdmin))->toThrow(RejectedException::class);
        $this->assertModelExists($superAdmin->fresh());
    });

    test('95EVB-FR-USER-049: the managers refuse the super admin before any mutation (also 95EVB-FR-USER-050)', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        Livewire::test(UserManager::class)
            ->call('editUser', $superAdmin->id)
            ->assertSet('userModal', false);

        expect($superAdmin->fresh()->name)->toBe($superAdmin->name);

        Livewire::test(UserManager::class)
            ->set('confirmActionType', 'delete')
            ->set('confirmTarget', $superAdmin->id)
            ->call('confirmAction');

        $this->assertModelExists($superAdmin->fresh());
    });

    test('95EVB-FR-USER-053: the toggle obeys the super-admin lock rule and nobody flips their own switch (also 95EVB-NFR-USER-007)', function (): void {
        $admin = User::factory()->create(['status' => 'verified']);
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $superAdmin = User::factory()->create(['status' => 'verified']);
        $superAdmin->assignRole('super_admin');

        expect(fn () => app(ToggleUserStatusAction::class)->execute($superAdmin))->toThrow(RejectedException::class);
        expect($superAdmin->fresh()->status)->toBe(AccountStatus::VERIFIED);

        expect(fn () => app(ToggleUserStatusAction::class)->execute($admin))->toThrow(RejectedException::class);
        expect($admin->fresh()->status)->toBe(AccountStatus::VERIFIED);

        expect(fn () => app(SetUserStatusAction::class)->execute(new SetUserStatusData(
            userId: $admin->id,
            newStatus: AccountStatus::SUSPENDED,
        )))->toThrow(RejectedException::class);
        expect($admin->fresh()->status)->toBe(AccountStatus::VERIFIED);
    });

    test('95EVB-FR-USER-055: archival walks hundred-row chunks and never retires the break-glass (also 95EVB-FR-USER-056)', function (): void {
        $ids = [];
        for ($i = 0; $i < 105; $i++) {
            $student = User::factory()->create(['status' => 'verified']);
            $student->assignRole('student');
            $ids[] = $student->id;
        }
        $superAdmin = User::factory()->create(['status' => 'verified']);
        $superAdmin->assignRole('super_admin');
        $ids[] = $superAdmin->id;

        $seen = [];
        DB::listen(function ($query) use (&$seen): void {
            $seen[] = $query->sql;
        });

        $count = app(ArchiveStudentAccountsAction::class)->execute(User::whereIn('id', $ids));

        expect($count)->toBe(105);
        expect(User::whereIn('id', $ids)->where('status', 'archived')->count())->toBe(105);
        expect($superAdmin->fresh()->status)->toBe(AccountStatus::VERIFIED);

        $chunkSelects = array_values(array_filter($seen, fn (string $sql): bool => str_contains($sql, 'offset')));
        expect($chunkSelects)->not->toBeEmpty();
        foreach ($chunkSelects as $sql) {
            expect($sql)->toContain('100');
        }
    });

    test('95EVB-FR-USER-057: reset revokes activation tokens so re-activation is required (also 95EVB-UC-USER-005)', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $user = User::factory()->create();
        AccessToken::generateFor($user, 'activation');
        expect(AccessToken::where('user_id', $user->id)->where('token_type', 'activation')->whereNull('revoked_at')->exists())->toBeTrue();

        app(RevokeUserActivationTokensAction::class)->execute($user);

        expect(AccessToken::where('user_id', $user->id)->where('token_type', 'activation')->whereNull('revoked_at')->exists())->toBeFalse();
        expect(AccessToken::where('user_id', $user->id)->where('token_type', 'activation')->whereNotNull('revoked_at')->exists())->toBeTrue();

        $second = User::factory()->create();
        AccessToken::generateFor($second, 'activation');

        Livewire::test(UserManager::class)->call('resetPassword', $second->id);

        expect(AccessToken::where('user_id', $second->id)->where('token_type', 'activation')->whereNull('revoked_at')->exists())->toBeFalse();
    });

    test('95EVB-UC-USER-001: admin creates a single user with credentials, profile, role, and notices', function (): void {
        Event::fake();
        Notification::fake();

        $result = app(CreateUserAction::class)->execute(new CreateUserData(
            user: ['name' => 'First Day Student', 'email' => 'first.day.student@example.com'],
            profile: ['phone' => '0812000001'],
            roles: ['student'],
        ));

        expect($result->username)->not->toBeEmpty();
        $this->assertDatabaseHas('users', ['id' => $result->id, 'email' => 'first.day.student@example.com']);
        $this->assertDatabaseHas('profiles', ['user_id' => $result->id, 'phone' => '0812000001']);
        expect($result->hasRole('student'))->toBeTrue();
        $this->assertDatabaseHas('access_tokens', ['user_id' => $result->id, 'token_type' => 'activation']);
        Notification::assertSentTo($result, ActivationCodeNotification::class);
        Notification::assertSentTo($result, WelcomeNotification::class);
        Event::assertDispatched(UserCreated::class);
    });

    test('95EVB-UC-USER-002: admin fixes user details and profile atomically with an event', function (): void {
        Event::fake();
        $user = User::factory()->create(['name' => 'Mispelled Name']);
        $user->assignRole('student');
        Profile::factory()->create(['user_id' => $user->id, 'phone' => '0800000000']);

        $result = app(UpdateUserAction::class)->execute(new UpdateUserData(
            userId: $user->id,
            user: ['name' => 'Corrected Name', 'email' => 'corrected.name@example.com'],
            profile: ['phone' => '0812999999'],
            roles: ['student', 'teacher'],
        ));

        expect($result->id)->toBe($user->id);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Corrected Name', 'email' => 'corrected.name@example.com']);
        $this->assertDatabaseHas('profiles', ['user_id' => $user->id, 'phone' => '0812999999']);
        expect($result->fresh()->hasRole('teacher'))->toBeTrue();
        Event::assertDispatched(UserUpdated::class);
    });

    test('95EVB-UC-USER-003: admin locks a lab group while self and super admin are refused (also 95EVB-NFR-USER-003)', function (): void {
        Event::fake();
        $admin = User::factory()->create(['status' => 'verified']);
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $superAdmin = User::factory()->create(['status' => 'verified']);
        $superAdmin->assignRole('super_admin');
        $first = User::factory()->create(['status' => 'verified']);
        $second = User::factory()->create(['status' => 'verified']);

        $moved = 0;
        $skipped = 0;
        foreach ([$first->id, $second->id, $admin->id, $superAdmin->id] as $id) {
            try {
                app(SetUserStatusAction::class)->execute(new SetUserStatusData(
                    userId: $id,
                    newStatus: AccountStatus::SUSPENDED,
                    reason: 'lab incident',
                ));
                $moved++;
            } catch (RejectedException) {
                $skipped++;
            }
        }

        expect($moved)->toBe(2)->and($skipped)->toBe(2);
        expect($first->fresh()->status)->toBe(AccountStatus::SUSPENDED);
        expect($second->fresh()->status)->toBe(AccountStatus::SUSPENDED);
        expect($admin->fresh()->status)->toBe(AccountStatus::VERIFIED);
        expect($superAdmin->fresh()->status)->toBe(AccountStatus::VERIFIED);
        Event::assertDispatched(UserStatusChanged::class, 2);
    });

    test('95EVB-UC-USER-004: the super admin survives deletion at every layer (also 95EVB-NFR-USER-004)', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        expect(fn () => (new UserObserver)->deleting($superAdmin))->toThrow(RejectedException::class);
        expect(fn () => $superAdmin->delete())->toThrow(RejectedException::class);
        expect(fn () => app(DeleteUserAction::class)->execute($superAdmin))->toThrow(RejectedException::class);

        Livewire::test(UserManager::class)
            ->set('confirmActionType', 'delete')
            ->set('confirmTarget', $superAdmin->id)
            ->call('confirmAction');

        Livewire::test(UserManager::class)
            ->call('editUser', $superAdmin->id)
            ->assertSet('userModal', false);

        $this->assertModelExists($superAdmin->fresh());
    });

    test('95EVB-UC-USER-006: admin mass-archives a finished cohort with the super admin skipped', function (): void {
        $students = [];
        for ($i = 0; $i < 4; $i++) {
            $student = User::factory()->create(['status' => 'verified']);
            $student->assignRole('student');
            $students[] = $student->id;
        }
        $superAdmin = User::factory()->create(['status' => 'verified']);
        $superAdmin->assignRole('super_admin');

        $count = app(ArchiveStudentAccountsAction::class)->execute(
            User::role('student')->whereIn('id', [...$students, $superAdmin->id]),
        );

        expect($count)->toBe(4);
        foreach ($students as $id) {
            $this->assertDatabaseHas('users', ['id' => $id, 'status' => 'archived']);
        }
        expect($superAdmin->fresh()->status)->toBe(AccountStatus::VERIFIED);
    });

    test('95EVB-FR-USER-011: every CRUD action wears the command uniform and rolls back together', function (): void {
        foreach (
            [
                new CreateUserAction,
                new UpdateUserAction,
                new DeleteUserAction,
                app(BatchDeleteUserAction::class),
                new SetUserStatusAction,
                new ToggleUserStatusAction,
                new ArchiveStudentAccountsAction,
                new RevokeUserActivationTokensAction,
                new UpdateProfileAction,
            ] as $action
        ) {
            expect($action)->toBeInstanceOf(BaseCommandAction::class);
        }

        $before = User::count();
        $existing = User::factory()->create();

        expect(fn () => app(CreateUserAction::class)->execute(new CreateUserData(
            user: ['name' => 'Rollback Proof', 'username' => 'rollbackproof', 'email' => $existing->email, 'password' => 'secret-3'],
            profile: ['phone' => '0813000001'],
            sendNotification: false,
        )))->toThrow(ValidationException::class);

        expect(User::count())->toBe($before + 1);
        $this->assertDatabaseMissing('profiles', ['phone' => '0813000001']);
    });

    test('95EVB-NFR-USER-005: passwords hash on every write path and plaintext never rests', function (): void {
        $user = app(CreateUserAction::class)->execute(new CreateUserData(
            user: ['name' => 'Hash Only', 'email' => 'hash.only@example.com', 'password' => 'SecretTest123'],
            sendNotification: false,
        ));

        expect(Hash::check('SecretTest123', $user->fresh()->getAttributes()['password']))->toBeTrue();
        expect($user->fresh()->getAttributes()['password'])->not->toBe('SecretTest123');

        app(UpdateUserAction::class)->execute(new UpdateUserData(
            userId: $user->id,
            user: ['password' => 'OtherSecret456'],
        ));

        expect(Hash::check('OtherSecret456', $user->fresh()->getAttributes()['password']))->toBeTrue();
        expect(Hash::check('SecretTest123', $user->fresh()->getAttributes()['password']))->toBeFalse();
    });

    test('95EVB-NFR-USER-006: no status write skips the transition check', function (): void {
        $user = User::factory()->create(['status' => 'provisioned']);

        expect(fn () => app(SetUserStatusAction::class)->execute(new SetUserStatusData(
            userId: $user->id,
            newStatus: AccountStatus::ARCHIVED,
            skipAuthCheck: true,
        )))->toThrow(RejectedException::class);
        expect($user->fresh()->status)->toBe(AccountStatus::PROVISIONED);

        app(SetUserStatusAction::class)->execute(new SetUserStatusData(
            userId: $user->id,
            newStatus: AccountStatus::ACTIVATED,
            skipAuthCheck: true,
        ));
        expect($user->fresh()->status)->toBe(AccountStatus::ACTIVATED);
    });

    test('95EVB-NFR-USER-008: system moves may bypass the acting-user check via an explicit flag', function (): void {
        $user = User::factory()->create(['status' => 'provisioned']);
        $this->actingAs($user);

        expect(fn () => app(SetUserStatusAction::class)->execute(new SetUserStatusData(
            userId: $user->id,
            newStatus: AccountStatus::ACTIVATED,
        )))->toThrow(RejectedException::class);
        expect($user->fresh()->status)->toBe(AccountStatus::PROVISIONED);

        app(SetUserStatusAction::class)->execute(new SetUserStatusData(
            userId: $user->id,
            newStatus: AccountStatus::ACTIVATED,
            skipAuthCheck: true,
        ));
        expect($user->fresh()->status)->toBe(AccountStatus::ACTIVATED);
    });

    test('95EVB-NFR-USER-010: the account lands even when delivery of its notices cannot be confirmed', function (): void {
        Notification::fake();

        $user = app(CreateUserAction::class)->execute(new CreateUserData(
            user: ['name' => 'Outage Birth', 'email' => 'outage.birth@example.com'],
        ));

        $this->assertModelExists($user);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'outage.birth@example.com']);
        $this->assertDatabaseHas('access_tokens', ['user_id' => $user->id, 'token_type' => 'activation']);
    });

    test('95EVB-NFR-USER-012: every status badge pairs its color with a translated label', function (): void {
        foreach (AccountStatus::cases() as $status) {
            expect($status->color())->not->toBeEmpty()
                ->and($status->label())->not->toBeEmpty()
                ->and($status->label())->not->toBe($status->value);
        }
    });
});
