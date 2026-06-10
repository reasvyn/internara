<?php

declare(strict_types=1);

use App\User\Models\User;
use App\User\Notifications\Actions\MarkAsReadAction;
use App\User\Notifications\Actions\SendNotificationAction;
use App\User\Notifications\Events\NotificationRead;
use App\User\Notifications\Events\NotificationSent;
use App\User\Notifications\Models\Notification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Role;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    Role::create(['name' => 'superadmin', 'guard_name' => 'web']);
});

test('send notification dispatches event and invalidates cache', function () {
    $user = User::factory()->create();

    Event::fake([NotificationSent::class]);

    $notification = app(SendNotificationAction::class)->execute(
        userId: $user->id,
        type: 'info',
        title: 'Test',
    );

    Event::assertDispatched(NotificationSent::class);
    expect($notification->title)->toBe('Test');
});

test('mark as read dispatches event', function () {
    $user = User::factory()->create();
    $notification = Notification::factory()->create([
        'user_id' => $user->id,
        'is_read' => false,
    ]);

    Event::fake([NotificationRead::class]);

    app(MarkAsReadAction::class)->execute($notification);

    Event::assertDispatched(NotificationRead::class);
});
