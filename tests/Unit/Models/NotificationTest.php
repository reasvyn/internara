<?php

declare(strict_types=1);

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can be created with factory', function () {
    $notification = Notification::factory()->create();

    expect($notification)->toBeInstanceOf(Notification::class)
        ->and($notification->id)->toBeUuid();
});

it('casts attributes correctly', function () {
    $notification = Notification::factory()->create([
        'data' => ['key' => 'value'],
        'is_read' => true,
        'read_at' => now(),
    ]);

    expect($notification->data)->toBe(['key' => 'value'])
        ->and($notification->is_read)->toBeTrue()
        ->and($notification->read_at)->toBeInstanceOf(Carbon\Carbon::class);
});

it('belongs to user', function () {
    $user = User::factory()->create();
    $notification = Notification::factory()->create(['user_id' => $user->id]);

    expect($notification->user)->toBeInstanceOf(User::class)
        ->and($notification->user->id)->toBe($user->id);
});

it('delegates status checks to entity', function () {
    $notification = Notification::factory()->create(['is_read' => false]);
    expect($notification->asNotificationStatus()->isUnread())->toBeTrue();

    $notification->update(['is_read' => true, 'read_at' => now()]);
    expect($notification->fresh()->asNotificationStatus()->isUnread())->toBeFalse();
});
