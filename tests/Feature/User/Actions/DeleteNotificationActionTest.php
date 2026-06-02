<?php

declare(strict_types=1);

use App\Domain\User\Actions\DeleteNotificationAction;
use App\Domain\User\Models\Notification;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

describe('DeleteNotificationAction', function () {
    beforeEach(function () {
        Cache::flush();
    });

    it('deletes a notification', function () {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create();

        app(DeleteNotificationAction::class)->execute($notification);

        expect(Notification::find($notification->id))->toBeNull();
    });

    it('clears unread cache for the notification owner', function () {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create();

        Cache::put('notification.unread:'.$user->id, ['cached'], 3600);

        app(DeleteNotificationAction::class)->execute($notification);

        expect(Cache::has('notification.unread:'.$user->id))->toBeFalse();
    });
});
