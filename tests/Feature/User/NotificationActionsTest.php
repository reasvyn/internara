<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Domain\User\Actions\DeleteNotificationAction;
use App\Domain\User\Actions\MarkAllAsReadAction;
use App\Domain\User\Actions\MarkAsReadAction;
use App\Domain\User\Actions\MarkBatchAsReadAction;
use App\Domain\User\Actions\SendNotificationAction;
use App\Domain\User\Models\Notification;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

describe('SendNotificationAction', function () {
    it('creates a notification for a user', function () {
        $user = User::factory()->create();

        $notification = app(SendNotificationAction::class)->execute(
            userId: $user->id,
            type: 'test',
            title: 'Hello',
            message: 'Test message',
        );

        expect($notification)->toBeInstanceOf(Notification::class)
            ->and($notification->user_id)->toBe($user->id)
            ->and($notification->title)->toBe('Hello')
            ->and($notification->message)->toBe('Test message')
            ->and($notification->is_read)->toBeFalse();
    });

    it('creates notification with optional data and link', function () {
        $user = User::factory()->create();

        $notification = app(SendNotificationAction::class)->execute(
            userId: $user->id,
            type: 'alert',
            title: 'Alert',
            message: 'Check this',
            data: ['key' => 'value'],
            link: '/dashboard',
        );

        expect($notification->data)->toBe(['key' => 'value'])
            ->and($notification->link)->toBe('/dashboard');
    });

    it('throws for nonexistent user', function () {
        app(SendNotificationAction::class)->execute(
            userId: 'nonexistent-id',
            type: 'test',
            title: 'Test',
        );
    })->throws(ModelNotFoundException::class);
});

describe('MarkAsReadAction', function () {
    it('marks a notification as read', function () {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create(['is_read' => false]);

        app(MarkAsReadAction::class)->execute($notification);

        expect($notification->fresh()->is_read)->toBeTrue()
            ->and($notification->fresh()->read_at)->not->toBeNull();
    });

    it('does nothing if already read', function () {
        $user = User::factory()->create();
        $readAt = now()->subHour();
        $notification = Notification::factory()->for($user)->create(['is_read' => true, 'read_at' => $readAt]);

        app(MarkAsReadAction::class)->execute($notification);

        expect($notification->fresh()->read_at->toIso8601String())->toBe($readAt->toIso8601String());
    });
});

describe('MarkAllAsReadAction', function () {
    it('marks all unread notifications as read', function () {
        $user = User::factory()->create();
        Notification::factory()->for($user)->count(3)->create(['is_read' => false]);
        Notification::factory()->for($user)->create(['is_read' => true]);

        $count = app(MarkAllAsReadAction::class)->execute($user->id);

        expect($count)->toBe(3)
            ->and(Notification::where('user_id', $user->id)->where('is_read', false)->count())->toBe(0);
    });

    it('returns 0 when no unread notifications', function () {
        $user = User::factory()->create();

        $count = app(MarkAllAsReadAction::class)->execute($user->id);

        expect($count)->toBe(0);
    });
});

describe('MarkBatchAsReadAction', function () {
    it('marks selected notifications as read', function () {
        $user = User::factory()->create();
        $n1 = Notification::factory()->for($user)->create(['is_read' => false]);
        $n2 = Notification::factory()->for($user)->create(['is_read' => false]);
        Notification::factory()->for($user)->create(['is_read' => false]);

        $count = app(MarkBatchAsReadAction::class)->execute($user->id, [$n1->id, $n2->id]);

        expect($count)->toBe(2)
            ->and($n1->fresh()->is_read)->toBeTrue()
            ->and($n2->fresh()->is_read)->toBeTrue();
    });

    it('ignores other users notifications', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $n1 = Notification::factory()->for($user1)->create(['is_read' => false]);

        $count = app(MarkBatchAsReadAction::class)->execute($user2->id, [$n1->id]);

        expect($count)->toBe(0);
    });
});

describe('DeleteNotificationAction', function () {
    it('deletes a notification', function () {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create();

        app(DeleteNotificationAction::class)->execute($notification);

        expect(Notification::find($notification->id))->toBeNull();
    });
});
