<?php

declare(strict_types=1);

use App\Actions\Notification\DeleteNotificationAction;
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
    it('deletes a notification', function () {
        $notification = NotificationFactory::new()->create();

        app(DeleteNotificationAction::class)->execute($notification);

        expect($notification->fresh())->toBeNull();
    });
});
