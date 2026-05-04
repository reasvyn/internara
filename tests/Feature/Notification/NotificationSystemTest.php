<?php

declare(strict_types=1);

namespace Tests\Feature\Notification;

use App\Domain\Notification\Actions\DeleteNotificationAction;
use App\Domain\Notification\Actions\GetNotificationsAction;
use App\Domain\Notification\Actions\MarkAsReadAction;
use App\Domain\Notification\Models\Notification;
use App\Domain\User\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('Notification System', function () {
    it('can get notifications via GetNotificationsAction', function () {
        $action = app(GetNotificationsAction::class);

        $notifications = $action->execute($this->user->id, false, 50);

        expect($notifications)->toBeCollection();
    });

    it('can mark notification as read via MarkAsReadAction', function () {
        $notification = Notification::create([
            'user_id' => $this->user->id,
            'type' => 'test',
            'title' => 'Test',
            'message' => 'Test',
            'data' => ['message' => 'Test'],
            'is_read' => false,
        ]);

        $action = app(MarkAsReadAction::class);
        $action->execute($notification);

        expect($notification->fresh()->is_read)->toBeTrue();
    });

    it('can delete notification via DeleteNotificationAction', function () {
        $notification = Notification::create([
            'user_id' => $this->user->id,
            'type' => 'test',
            'title' => 'Test',
            'message' => 'Test',
            'data' => ['message' => 'Test'],
        ]);

        $action = app(DeleteNotificationAction::class);
        $action->execute($notification);

        expect(Notification::find($notification->id))->toBeNull();
    });
});
