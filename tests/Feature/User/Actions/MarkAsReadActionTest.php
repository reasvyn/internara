<?php

declare(strict_types=1);

use App\Domain\User\Actions\MarkAsReadAction;
use App\Domain\User\Models\Notification;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

describe('MarkAsReadAction', function () {
    beforeEach(function () {
        Cache::flush();
    });

    it('marks unread notification as read', function () {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->unread()->create();

        $result = app(MarkAsReadAction::class)->execute($notification);

        expect($result->is_read)->toBeTrue();
        expect($result->read_at)->not->toBeNull();
    });

    it('does not change already read notification', function () {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->read()->create();
        $originalReadAt = $notification->read_at;

        $result = app(MarkAsReadAction::class)->execute($notification);

        expect($result->is_read)->toBeTrue();
    });

    it('clears unread cache', function () {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->unread()->create();

        Cache::put('notification.unread:'.$user->id, ['cached'], 3600);

        app(MarkAsReadAction::class)->execute($notification);

        expect(Cache::has('notification.unread:'.$user->id))->toBeFalse();
    });

    it('returns fresh notification instance', function () {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->unread()->create();

        $result = app(MarkAsReadAction::class)->execute($notification);

        expect($result->id)->toBe($notification->id);
    });
});
