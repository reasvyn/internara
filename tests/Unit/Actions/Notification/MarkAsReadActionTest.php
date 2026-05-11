<?php

declare(strict_types=1);

use App\Actions\Notification\MarkAsReadAction;
use App\Models\Notification;
use Database\Factories\NotificationFactory;
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
    it('marks a single notification as read', function () {
        $notification = NotificationFactory::new()->unread()->create();

        $result = app(MarkAsReadAction::class)->execute($notification);

        expect($result->is_read)->toBeTrue()
            ->and($result->read_at)->not->toBeNull();
    });

    it('does not change already read notification', function () {
        $notification = NotificationFactory::new()->read()->create();

        $result = app(MarkAsReadAction::class)->execute($notification);

        expect($result->is_read)->toBeTrue();
    });
});
