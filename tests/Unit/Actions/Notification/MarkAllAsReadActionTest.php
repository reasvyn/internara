<?php

declare(strict_types=1);

use App\Actions\Notification\MarkAllAsReadAction;
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
    it('marks all unread notifications as read', function () {
        $user = UserFactory::new()->create();
        NotificationFactory::new()->unread()->create(['user_id' => $user->id]);
        NotificationFactory::new()->unread()->create(['user_id' => $user->id]);

        $count = app(MarkAllAsReadAction::class)->execute($user->id);

        expect($count)->toBe(2);
    });

    it('returns zero when no unread notifications', function () {
        $user = UserFactory::new()->create();
        NotificationFactory::new()->read()->create(['user_id' => $user->id]);

        $count = app(MarkAllAsReadAction::class)->execute($user->id);

        expect($count)->toBe(0);
    });
});
