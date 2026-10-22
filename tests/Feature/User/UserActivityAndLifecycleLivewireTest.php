<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Modules\Core\Models\ActivityLog;
use App\Modules\User\Actions\ReadActivityLogAction;
use App\Modules\User\Domain\AccountStatus\Actions\DetectUserAccountCloneAction;
use App\Modules\User\Domain\AccountStatus\Actions\LockUserAccountAction;
use App\Modules\User\Domain\AccountStatus\Actions\UnlockUserAccountAction;
use App\Modules\User\Domain\AccountStatus\Livewire\AccountLifecycleManager;
use App\Modules\User\Domain\Dashboard\Livewire\UserDashboard;
use App\Modules\User\Livewire\ActivityFeedManager;
use App\Modules\User\Livewire\RecentActivityList;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Collection;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

describe('95EVB: User Activity, Dashboard, and Lifecycle Livewire UI Components', function (): void {
    test('95EVB-FR-USER-040: UserDashboard renders authenticated user activities', function (): void {
        $user = User::factory()->create(['name' => 'Dashboard User']);
        $user->assignRole('student');

        ActivityLog::create([
            'log_name' => 'default',
            'description' => 'logged_in',
            'causer_type' => User::class,
            'causer_id' => $user->id,
        ]);

        $component = Livewire::actingAs($user)->test(UserDashboard::class);

        $component->assertOk();
        expect($component->instance()->getUser()->id)->toBe($user->id)
            ->and($component->instance()->getRecentActivities()->count())->toBe(1);
    });

    test('95EVB-FR-USER-041: RecentActivityList renders empty and non-empty activity states', function (): void {
        $user = User::factory()->create();

        // Empty state
        Livewire::actingAs($user)->test(RecentActivityList::class)
            ->assertOk()
            ->assertSee('No recent activity found.');

        // Non-empty state
        ActivityLog::create([
            'log_name' => 'default',
            'description' => 'profile_updated',
            'causer_type' => User::class,
            'causer_id' => $user->id,
            'properties' => ['ip_address' => '127.0.0.1'],
        ]);

        $test = Livewire::actingAs($user)->test(RecentActivityList::class);
        $test->assertOk();
        expect($test->instance()->activities->count())->toBe(1);
    });

    test('95EVB-FR-USER-042: ActivityFeedManager renders paginated activity list', function (): void {
        $user = User::factory()->create();

        ActivityLog::create([
            'log_name' => 'default',
            'description' => 'password_changed',
            'causer_type' => User::class,
            'causer_id' => $user->id,
        ]);

        Livewire::actingAs($user)->test(ActivityFeedManager::class)
            ->assertOk()
            ->assertSee('password_changed');
    });

    test('95EVB-FR-USER-043: AccountLifecycleManager authorizes admin and handles lock and unlock actions', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $targetUser = User::factory()->create();
        $targetUser->assignRole('student');

        $component = Livewire::actingAs($admin)->test(AccountLifecycleManager::class);
        $component->assertOk();

        // Trigger lock dialog setup
        $component->call('askLock', (string) $targetUser->id);
        expect($component->get('confirmActionType'))->toBe('lock')
            ->and($component->get('confirmTarget'))->toBe((string) $targetUser->id);

        // Confirm lock
        $component->call(
            'confirmAction',
            app(LockUserAccountAction::class),
            app(UnlockUserAccountAction::class),
        );
        expect($component->get('confirmTarget'))->toBeNull();

        // Trigger unlock dialog setup
        $component->call('askUnlock', (string) $targetUser->id);
        expect($component->get('confirmActionType'))->toBe('unlock')
            ->and($component->get('confirmTarget'))->toBe((string) $targetUser->id);

        // Confirm unlock
        $component->call(
            'confirmAction',
            app(LockUserAccountAction::class),
            app(UnlockUserAccountAction::class),
        );
        expect($component->get('confirmTarget'))->toBeNull();

        // Clone detection method
        $clones = $component->instance()->detectClones(app(DetectUserAccountCloneAction::class));
        expect($clones)->toBeInstanceOf(Collection::class);
    });

    test('95EVB-FR-USER-044: AccountLifecycleManager denies unauthorized access to non-admin roles', function (): void {
        $student = User::factory()->create();
        $student->assignRole('student');

        Livewire::actingAs($student)->test(AccountLifecycleManager::class)
            ->assertForbidden();
    });

    test('95EVB-FR-USER-042: ReadActivityLogAction paginates activities for a user in descending chronological order', function (): void {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        ActivityLog::create([
            'log_name' => 'default',
            'description' => 'first_action',
            'causer_type' => User::class,
            'causer_id' => $user->id,
            'created_at' => now()->subHours(2),
        ]);

        ActivityLog::create([
            'log_name' => 'default',
            'description' => 'second_action',
            'causer_type' => User::class,
            'causer_id' => $user->id,
            'created_at' => now()->subHour(),
        ]);

        ActivityLog::create([
            'log_name' => 'default',
            'description' => 'other_user_action',
            'causer_type' => User::class,
            'causer_id' => $otherUser->id,
        ]);

        $paginator = app(ReadActivityLogAction::class)->execute((string) $user->id, 10);

        expect($paginator->total())->toBe(2)
            ->and($paginator->items()[0]->description)->toBe('second_action')
            ->and($paginator->items()[1]->description)->toBe('first_action');
    });
});
