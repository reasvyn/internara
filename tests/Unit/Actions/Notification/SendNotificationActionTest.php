<?php

declare(strict_types=1);

use App\Actions\Notification\SendNotificationAction;
use App\Models\Notification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('sends a notification to a user', function () {
        $user = UserFactory::new()->create();

        $notification = app(SendNotificationAction::class)->execute(
            userId: $user->id,
            type: 'info',
            title: 'Welcome',
            message: 'Welcome to the platform!',
            data: ['key' => 'value'],
            link: '/dashboard',
        );

        expect($notification)->toBeInstanceOf(Notification::class)
            ->and($notification->user_id)->toBe($user->id)
            ->and($notification->type)->toBe('info')
            ->and($notification->title)->toBe('Welcome')
            ->and($notification->message)->toBe('Welcome to the platform!')
            ->and($notification->is_read)->toBeFalse();
    });

    it('throws ModelNotFoundException for invalid user', function () {
        expect(fn () => app(SendNotificationAction::class)->execute(
            userId: 'non-existent',
            type: 'info',
            title: 'Test',
        ))->toThrow(ModelNotFoundException::class);
    });
});
