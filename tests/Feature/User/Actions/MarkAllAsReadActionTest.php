<?php

declare(strict_types=1);

use App\Domain\User\Actions\MarkAllAsReadAction;
use App\Domain\User\Models\Notification;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

describe('MarkAllAsReadAction', function () {
    beforeEach(function () {
        Cache::flush();
    });

    it('marks all unread notifications as read', function () {
        $user = User::factory()->create();
        Notification::factory()->for($user)->unread()->count(3)->create();

        $updated = app(MarkAllAsReadAction::class)->execute($user->id);

        expect($updated)->toBe(3);
        expect(Notification::where('user_id', $user->id)->where('is_read', false)->count())->toBe(0);
    });

    it('returns zero when no unread notifications', function () {
        $user = User::factory()->create();
        Notification::factory()->for($user)->read()->count(2)->create();

        $updated = app(MarkAllAsReadAction::class)->execute($user->id);

        expect($updated)->toBe(0);
    });

    it('clears unread cache', function () {
        $user = User::factory()->create();
        Notification::factory()->for($user)->unread()->create();

        Cache::put('notification.unread:'.$user->id, ['cached'], 3600);

        app(MarkAllAsReadAction::class)->execute($user->id);

        expect(Cache::has('notification.unread:'.$user->id))->toBeFalse();
    });

    it('only marks notifications for the specified user', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        Notification::factory()->for($user1)->unread()->create();
        Notification::factory()->for($user2)->unread()->create();

        app(MarkAllAsReadAction::class)->execute($user1->id);

        expect(Notification::where('user_id', $user1->id)->where('is_read', true)->count())->toBe(1);
        expect(Notification::where('user_id', $user2->id)->where('is_read', false)->count())->toBe(1);
    });
});
