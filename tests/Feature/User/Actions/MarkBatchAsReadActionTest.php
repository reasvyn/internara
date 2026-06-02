<?php

declare(strict_types=1);

use App\Domain\User\Actions\MarkBatchAsReadAction;
use App\Domain\User\Models\Notification;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

describe('MarkBatchAsReadAction', function () {
    beforeEach(function () {
        Cache::flush();
    });

    it('marks specific notifications as read', function () {
        $user = User::factory()->create();
        $notifications = Notification::factory()->for($user)->unread()->count(3)->create();
        $ids = $notifications->take(2)->pluck('id')->toArray();

        $updated = app(MarkBatchAsReadAction::class)->execute($user->id, $ids);

        expect($updated)->toBe(2);
    });

    it('does not mark notifications from other users', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $n1 = Notification::factory()->for($user1)->unread()->create();
        $n2 = Notification::factory()->for($user2)->unread()->create();

        $updated = app(MarkBatchAsReadAction::class)->execute($user1->id, [$n1->id, $n2->id]);

        expect($updated)->toBe(1);
    });

    it('clears unread cache', function () {
        $user = User::factory()->create();
        $notifications = Notification::factory()->for($user)->unread()->count(2)->create();
        $ids = $notifications->pluck('id')->toArray();

        Cache::put('notification.unread:'.$user->id, ['cached'], 3600);

        app(MarkBatchAsReadAction::class)->execute($user->id, $ids);

        expect(Cache::has('notification.unread:'.$user->id))->toBeFalse();
    });
});
