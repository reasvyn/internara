<?php

declare(strict_types=1);

use App\Actions\Notification\GetNotificationsAction;
use App\Models\Notification;
use Database\Factories\NotificationFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeAll(function () {
    require_once getcwd().'/app/Models/Notification.php';
    class_alias(
        Notification::class,
        Notification\Notification::class,
    );
});

describe('execute', function () {
    beforeEach(function () {
        $this->user = UserFactory::new()->create();
        NotificationFactory::new()->count(3)->create(['user_id' => $this->user->id]);
    });

    it('returns notifications for a user', function () {
        $notifications = app(GetNotificationsAction::class)->execute($this->user->id);

        expect($notifications)->toHaveCount(3);
    });

    it('filters unread only', function () {
        NotificationFactory::new()->unread()->create(['user_id' => $this->user->id]);

        $notifications = app(GetNotificationsAction::class)->execute($this->user->id, unreadOnly: true);

        foreach ($notifications as $n) {
            expect($n->is_read)->toBeFalse();
        }
    });

    it('respects limit parameter', function () {
        $notifications = app(GetNotificationsAction::class)->execute($this->user->id, limit: 2);

        expect($notifications)->toHaveCount(2);
    });

    it('returns empty collection when no notifications', function () {
        $newUser = UserFactory::new()->create();

        $notifications = app(GetNotificationsAction::class)->execute($newUser->id);

        expect($notifications)->toBeEmpty();
    });
});
