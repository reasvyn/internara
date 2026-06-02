<?php

declare(strict_types=1);

use App\Domain\Core\Contracts\SendsNotifications;
use App\Domain\User\Actions\SendNotificationAction;
use App\Domain\User\Models\Notification;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

describe('SendNotificationAction', function () {
    beforeEach(function () {
        Cache::flush();
    });

    it('creates a notification', function () {
        $user = User::factory()->create();

        $notification = app(SendNotificationAction::class)->execute(
            userId: $user->id,
            type: 'info',
            title: 'Test Notification',
            message: 'This is a test',
        );

        expect($notification)->toBeInstanceOf(Notification::class)
            ->and($notification->user_id)->toBe($user->id)
            ->and($notification->type)->toBe('info')
            ->and($notification->title)->toBe('Test Notification')
            ->and($notification->message)->toBe('This is a test')
            ->and($notification->is_read)->toBeFalse();
    });

    it('creates notification with all optional fields', function () {
        $user = User::factory()->create();

        $notification = app(SendNotificationAction::class)->execute(
            userId: $user->id,
            type: 'warning',
            title: 'Warning',
            message: 'Be careful',
            data: ['severity' => 'high'],
            link: '/settings',
        );

        expect($notification->data)->toBe(['severity' => 'high'])
            ->and($notification->link)->toBe('/settings');
    });

    it('clears unread cache after creation', function () {
        $user = User::factory()->create();

        Cache::put('notification.unread:'.$user->id, ['cached'], 3600);

        app(SendNotificationAction::class)->execute(
            userId: $user->id,
            type: 'info',
            title: 'Test',
        );

        expect(Cache::has('notification.unread:'.$user->id))->toBeFalse();
    });

    it('validates required fields', function () {
        $user = User::factory()->create();

        expect(fn () => app(SendNotificationAction::class)->execute(
            userId: $user->id,
            type: '',
            title: '',
        ))->toThrow(ValidationException::class);
    });

    it('implements SendsNotifications contract', function () {
        $action = app(SendNotificationAction::class);

        expect($action)->toBeInstanceOf(SendsNotifications::class);
    });
});
