<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\AccessToken\Models\AccessToken;
use App\Modules\User\Domain\Profile\Actions\UpdateProfileAction;
use App\Modules\User\Domain\Profile\Data\UpdateProfileData;
use App\Modules\User\Domain\Profile\Models\Profile;
use App\Modules\User\Domain\UserManagement\Actions\ArchiveStudentAccountsAction;
use App\Modules\User\Domain\UserManagement\Actions\BatchDeleteUserAction;
use App\Modules\User\Domain\UserManagement\Actions\CreateUserAction;
use App\Modules\User\Domain\UserManagement\Actions\RevokeUserActivationTokensAction;
use App\Modules\User\Domain\UserManagement\Actions\SetUserStatusAction;
use App\Modules\User\Domain\UserManagement\Actions\ToggleUserStatusAction;
use App\Modules\User\Domain\UserManagement\Actions\UpdateUserAction;
use App\Modules\User\Domain\UserManagement\Data\CreateUserData;
use App\Modules\User\Domain\UserManagement\Data\SetUserStatusData;
use App\Modules\User\Domain\UserManagement\Data\UpdateUserData;
use App\Modules\User\Domain\UserManagement\Events\UserStatusChanged;
use App\Modules\User\Domain\UserManagement\Events\UserUpdated;
use App\Modules\User\Domain\UserManagement\Livewire\UserManager;
use App\Modules\User\Enums\AccountStatus;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

describe('95EVB: user NFR coverage', function (): void {
    test('95EVB-NFR-USER-001: the listing query eager-loads with a flat count as the roster grows', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $seedRoster = function (int $count, string $prefix): void {
            foreach (range(1, $count) as $i) {
                $user = User::factory()->create(['name' => "{$prefix} Roster {$i}"]);
                $user->assignRole('student');
                Profile::factory()->create(['user_id' => $user->id]);
            }
        };

        // Mount once: the initial render (stats, role options, row avatars)
        // runs outside the measurement so only the listing data path is counted.
        $manager = Livewire::test(UserManager::class)->instance();
        // Warm up once so schema/column caches settle before measuring.
        $manager->rows();

        $measureListingQueries = function () use ($manager): int {
            DB::flushQueryLog();
            DB::enableQueryLog();

            $page = $manager->rows();
            foreach ($page as $user) {
                $user->roles->pluck('name');
                $user->profile?->phone;
            }

            return count(DB::getQueryLog());
        };

        $seedRoster(6, 'Alpha');
        $smallRosterQueries = $measureListingQueries();

        $seedRoster(10, 'Beta');
        $largeRosterQueries = $measureListingQueries();

        expect($largeRosterQueries)->toBe($smallRosterQueries);
        expect($smallRosterQueries)->toBeLessThan(20);
    });

    test('95EVB-NFR-USER-002: archival walks multiple hundred-row chunks to retire a large cohort', function (): void {
        $ids = [];
        for ($i = 0; $i < 205; $i++) {
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

        expect($count)->toBe(205);
        expect(User::whereIn('id', $ids)->where('status', 'archived')->count())->toBe(205);
        expect($superAdmin->fresh()->status)->toBe(AccountStatus::VERIFIED);

        $chunkSelects = array_values(array_filter($seen, fn (string $sql): bool => str_contains($sql, 'offset')));
        expect(count($chunkSelects))->toBeGreaterThanOrEqual(3);
        foreach ($chunkSelects as $sql) {
            expect($sql)->toContain('100');
        }
    });

    test('95EVB-NFR-USER-011: successful mutations answer with a toast', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $target = User::factory()->create(['name' => 'Toast Update Target']);
        $target->assignRole('student');

        Livewire::test(UserManager::class)
            ->call('editUser', $target->id)
            ->set('form.name', 'Toast Update Applied')
            ->call('saveUser')
            ->assertDispatched('ts-ui:toast');

        $this->assertDatabaseHas('users', ['id' => $target->id, 'name' => 'Toast Update Applied']);

        $lockable = User::factory()->create(['status' => 'verified']);

        Livewire::test(UserManager::class)
            ->call('toggleStatus', $lockable->id)
            ->assertDispatched('ts-ui:toast');

        expect($lockable->fresh()->status)->toBe(AccountStatus::SUSPENDED);
        $this->assertDatabaseHas('users', ['id' => $lockable->id, 'status' => 'suspended']);
    });

    test('95EVB-NFR-USER-011: refused mutations answer with an error toast and no write', function (): void {
        $admin = User::factory()->create(['status' => 'verified']);
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $superAdmin = User::factory()->create(['status' => 'verified']);
        $superAdmin->assignRole('super_admin');

        Livewire::test(UserManager::class)
            ->call('toggleStatus', $superAdmin->id)
            ->assertDispatched('ts-ui:toast');

        expect($superAdmin->fresh()->status)->toBe(AccountStatus::VERIFIED);
        $this->assertDatabaseHas('users', ['id' => $superAdmin->id, 'status' => 'verified']);

        Livewire::test(UserManager::class)
            ->call('toggleStatus', $admin->id)
            ->assertDispatched('ts-ui:toast');

        expect($admin->fresh()->status)->toBe(AccountStatus::VERIFIED);
    });

    test('95EVB-NFR-USER-013: destructive deletes wait for confirmation', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $victim = User::factory()->create(['name' => 'Confirm Gate Victim']);

        Livewire::test(UserManager::class)
            ->call('askDeleteUser', $victim->id)
            ->assertSet('confirmActionType', 'delete')
            ->assertSet('confirmTarget', $victim->id);

        // Nothing is deleted by asking: the row survives until confirmation.
        $this->assertModelExists($victim->fresh());

        Livewire::test(UserManager::class)
            ->set('confirmActionType', 'delete')
            ->set('confirmTarget', $victim->id)
            ->call('confirmAction');

        $this->assertDatabaseMissing('users', ['id' => $victim->id]);
    });

    test('95EVB-NFR-USER-014: manager mutations flow through actions', function (): void {
        Event::fake([UserUpdated::class, UserStatusChanged::class]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $target = User::factory()->create(['name' => 'Delegated Update Target']);
        $target->assignRole('student');

        Livewire::test(UserManager::class)
            ->call('editUser', $target->id)
            ->set('form.name', 'Delegated Update Applied')
            ->call('saveUser');

        Event::assertDispatched(UserUpdated::class);
        $this->assertDatabaseHas('users', ['id' => $target->id, 'name' => 'Delegated Update Applied']);

        $lockable = User::factory()->create(['status' => 'verified']);

        Livewire::test(UserManager::class)->call('toggleStatus', $lockable->id);

        Event::assertDispatched(UserStatusChanged::class);
        $this->assertDatabaseHas('users', ['id' => $lockable->id, 'status' => 'suspended']);
    });

    test('95EVB-NFR-USER-016: create and update write their audit entries', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $user = app(CreateUserAction::class)->execute(new CreateUserData(
            user: ['name' => 'Audited Birth', 'email' => 'audited.birth@example.com'],
            sendNotification: false,
        ));

        $this->assertDatabaseHas('activity_log', ['description' => 'user_created']);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'audited.birth@example.com']);

        app(UpdateUserAction::class)->execute(new UpdateUserData(
            userId: $user->id,
            user: ['name' => 'Audited Renamed'],
        ));

        $this->assertDatabaseHas('activity_log', ['description' => 'user_updated']);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Audited Renamed']);
    });

    test('95EVB-NFR-USER-016: status moves write their audit entries', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $user = User::factory()->create(['status' => 'activated']);

        app(SetUserStatusAction::class)->execute(new SetUserStatusData(
            userId: $user->id,
            newStatus: AccountStatus::VERIFIED,
            reason: 'audited verification',
            skipAuthCheck: true,
        ));

        $this->assertDatabaseHas('activity_log', ['description' => 'user_status_changed']);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'verified']);

        $lockable = User::factory()->create(['status' => 'verified']);

        app(ToggleUserStatusAction::class)->execute($lockable);

        $this->assertDatabaseHas('activity_log', ['description' => 'user_status_toggled']);
        $this->assertDatabaseHas('users', ['id' => $lockable->id, 'status' => 'suspended']);
    });

    test('95EVB-NFR-USER-016: archive, revoke, profile, and batch delete write their audit entries', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $first = User::factory()->create();
        $second = User::factory()->create();
        $third = User::factory()->create();
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $result = app(BatchDeleteUserAction::class)->execute([$first->id, $second->id, $third->id, $superAdmin->id]);

        expect($result)->toBe(['deleted' => 3, 'skipped' => 1]);
        $this->assertDatabaseHas('activity_log', ['description' => 'users_batch_deleted']);

        $archivable = User::factory()->create(['status' => 'verified']);
        $archivable->assignRole('student');

        $archived = app(ArchiveStudentAccountsAction::class)->execute(User::where('id', $archivable->id));

        expect($archived)->toBe(1);
        $this->assertDatabaseHas('activity_log', ['description' => 'student_accounts_archived']);
        $this->assertDatabaseHas('users', ['id' => $archivable->id, 'status' => 'archived']);

        $revocable = User::factory()->create();
        AccessToken::generateFor($revocable, 'activation');

        app(RevokeUserActivationTokensAction::class)->execute($revocable);

        $this->assertDatabaseHas('activity_log', ['description' => 'activation_tokens_revoked']);
        expect(AccessToken::where('user_id', $revocable->id)->whereNull('revoked_at')->exists())->toBeFalse();

        $profileOwner = User::factory()->create();

        app(UpdateProfileAction::class)->execute(new UpdateProfileData(
            userId: $profileOwner->id,
            profile: ['phone' => '0814777001'],
        ));

        $this->assertDatabaseHas('activity_log', ['description' => 'profile_updated']);
        $this->assertDatabaseHas('profiles', ['user_id' => $profileOwner->id, 'phone' => '0814777001']);
    });

    test('95EVB-NFR-USER-017: manager strings resolve in both locales', function (): void {
        $keys = [
            'user.manager.name',
            'user.manager.email',
            'user.manager.roles',
            'user.manager.status',
            'user.manager.success_updated',
            'user.manager.success_deleted',
            'user.manager.status_changed',
            'user.manager.password_reset',
            'user.manager.cannot_delete_super_admin',
            'user.manager.cannot_edit_super_admin',
            'user.fields.full_name',
            'user.fields.email',
            'user.fields.phone',
            'common.actions.confirm_action',
            'common.actions.confirm_message',
            'common.actions.delete',
            'common.actions.cancel',
        ];

        foreach (['en', 'id'] as $locale) {
            app()->setLocale($locale);

            foreach ($keys as $key) {
                expect(__($key))->not->toBe($key, "missing {$locale} translation for {$key}");
            }
        }

        app()->setLocale('en');
        expect(__('user.manager.success_updated'))->toBe('User updated successfully.');
        app()->setLocale('id');
        expect(__('user.manager.success_updated'))->toBe('Pengguna berhasil diperbarui.');
    });
});
