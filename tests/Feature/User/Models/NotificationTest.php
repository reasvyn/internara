<?php

declare(strict_types=1);

use App\Domain\User\Models\Notification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('Notification', function () {
    it('casts data to array', function () {
        $notification = Notification::factory()->create(['data' => ['key' => 'value']]);

        expect($notification->data)->toBe(['key' => 'value']);
    });

    it('defaults is_read to false', function () {
        $notification = Notification::factory()->unread()->create();

        expect($notification->is_read)->toBeFalse();
    });

    it('can be marked as read', function () {
        $notification = Notification::factory()->read()->create();

        expect($notification->is_read)->toBeTrue();
        expect($notification->read_at)->not->toBeNull();
    });
});
