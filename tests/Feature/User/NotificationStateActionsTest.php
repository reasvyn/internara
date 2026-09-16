<?php

declare(strict_types=1);

use App\Modules\Core\Channels\Data\NotificationData;
use App\Modules\User\Domain\Notify\Actions\DeleteNotificationAction;
use App\Modules\User\Domain\Notify\Actions\MarkAllAsReadAction;
use App\Modules\User\Domain\Notify\Actions\MarkAsReadAction;
use App\Modules\User\Domain\Notify\Actions\MarkBatchAsReadAction;
use App\Modules\User\Domain\Notify\Actions\SendNotificationAction;
use App\Modules\User\Domain\Notify\Events\NotificationRead;
use App\Modules\User\Domain\Notify\Models\Notification;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

function unreadCacheKey(string $userId): string
{
    return config('cache-keys.notification_unread').$userId;
}

describe('TXR2H: notification state-change actions', function (): void {
    test('TXR2H-FR-NOTIF-017: mark-as-read stamps both flags and announces the read event', function (): void {
        Event::fake([NotificationRead::class]);

        $user = User::factory()->create();
        $notification = Notification::factory()->unread()->create(['user_id' => $user->id]);

        $result = app(MarkAsReadAction::class)->execute($notification);

        expect($result->is_read)->toBeTrue();
        expect($result->read_at)->not->toBeNull();
        expect($notification->fresh()->is_read)->toBeTrue();
        Event::assertDispatched(NotificationRead::class, fn (NotificationRead $e) => $e->notification->id === $notification->id);
    });

    test('TXR2H-NFR-NOTIF-005: retrying mark-as-read keeps the first timestamp and still succeeds', function (): void {
        $user = User::factory()->create();
        $notification = Notification::factory()->unread()->create(['user_id' => $user->id]);

        $first = app(MarkAsReadAction::class)->execute($notification);
        $firstReadAt = $first->read_at->toDateTimeString();

        $second = app(MarkAsReadAction::class)->execute($first->fresh());

        // NOTE (finding): the second call still emits NotificationRead — the write
        // itself is idempotent, the announcement is not silenced on retry.
        expect($second->is_read)->toBeTrue();
        expect($second->read_at->toDateTimeString())->toBe($firstReadAt);
        expect(Notification::where('user_id', $user->id)->count())->toBe(1);
    });

    test('TXR2H-FR-NOTIF-018: mark-all updates only the acting user rows and forgets their cache key', function (): void {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Notification::factory()->unread()->count(3)->create(['user_id' => $user->id]);
        Notification::factory()->read()->create(['user_id' => $user->id]);
        Notification::factory()->unread()->count(2)->create(['user_id' => $other->id]);
        Cache::put(unreadCacheKey($user->id), 3, 60);

        $updated = app(MarkAllAsReadAction::class)->execute($user->id);

        expect($updated)->toBe(3);
        expect(Notification::where('user_id', $user->id)->where('is_read', false)->count())->toBe(0);
        expect(Notification::where('user_id', $other->id)->where('is_read', false)->count())->toBe(2);
        expect(Cache::has(unreadCacheKey($user->id)))->toBeFalse();
    });

    test('TXR2H-FR-NOTIF-018: batch mark counts only owned unread rows and stays quiet on empty intersection', function (): void {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $mine = Notification::factory()->unread()->count(2)->create(['user_id' => $user->id]);
        $alreadyRead = Notification::factory()->read()->create(['user_id' => $user->id]);
        $foreign = Notification::factory()->unread()->create(['user_id' => $other->id]);
        Cache::put(unreadCacheKey($user->id), 2, 60);

        $ids = [$mine[0]->id, $mine[1]->id, $alreadyRead->id, $foreign->id];
        $updated = app(MarkBatchAsReadAction::class)->execute($user->id, $ids);

        expect($updated)->toBe(2);
        expect($foreign->fresh()->is_read)->toBeFalse();
        expect(Cache::has(unreadCacheKey($user->id)))->toBeFalse();

        expect(app(MarkBatchAsReadAction::class)->execute($user->id, [$foreign->id]))->toBe(0);
        expect($foreign->fresh()->is_read)->toBeFalse();
    });

    test('TXR2H-FR-NOTIF-019: delete removes the row inside a transaction and forgets the owner cache key', function (): void {
        $user = User::factory()->create();
        $notification = Notification::factory()->unread()->create(['user_id' => $user->id]);
        $keeper = Notification::factory()->unread()->create(['user_id' => $user->id]);
        Cache::put(unreadCacheKey($user->id), 2, 60);

        app(DeleteNotificationAction::class)->execute($notification);

        expect(Notification::where('id', $notification->id)->exists())->toBeFalse();
        expect(Notification::where('id', $keeper->id)->exists())->toBeTrue();
        expect(Cache::has(unreadCacheKey($user->id)))->toBeFalse();
    });

    test('TXR2H-NFR-NOTIF-001: every send, read, batch-read, and delete invalidates the cached count in the same request', function (): void {
        $user = User::factory()->create();
        $action = app(SendNotificationAction::class);

        Cache::put(unreadCacheKey($user->id), 99, 60);
        $first = $action->execute(new NotificationData(
            userId: $user->id, type: 'info', title: 'Kabar pertama',
        ));
        expect(Cache::has(unreadCacheKey($user->id)))->toBeFalse('send must invalidate');

        Cache::put(unreadCacheKey($user->id), 99, 60);
        app(MarkAsReadAction::class)->execute($first);
        expect(Cache::has(unreadCacheKey($user->id)))->toBeFalse('single read must invalidate');

        $second = $action->execute(new NotificationData(
            userId: $user->id, type: 'info', title: 'Kabar kedua',
        ));
        Cache::put(unreadCacheKey($user->id), 99, 60);
        app(MarkBatchAsReadAction::class)->execute($user->id, [$second->id]);
        expect(Cache::has(unreadCacheKey($user->id)))->toBeFalse('batch read must invalidate');

        Cache::put(unreadCacheKey($user->id), 99, 60);
        app(DeleteNotificationAction::class)->execute($second->fresh());
        expect(Cache::has(unreadCacheKey($user->id)))->toBeFalse('delete must invalidate');
    });

    test('TXR2H-FR-NOTIF-009: fan-out rows are discarded when the originating transaction rolls back', function (): void {
        $user = User::factory()->create();

        try {
            DB::transaction(function () use ($user) {
                app(SendNotificationAction::class)->execute(new NotificationData(
                    userId: $user->id, type: 'assignment_published', title: 'Tugas hantu',
                ));

                throw new RuntimeException('originating write failed');
            });
        } catch (RuntimeException) {
            // Expected: the business transaction failed.
        }

        expect(Notification::where('user_id', $user->id)->count())->toBe(0);
    });
});
