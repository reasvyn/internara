<?php

declare(strict_types=1);

use App\User\Models\User;
use App\User\Notifications\Actions\SendNotificationAction;
use App\User\Notifications\Models\Notification;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->action = app(SendNotificationAction::class);
});

test('sends notification to user', function () {
    $notification = $this->action->execute(
        userId: $this->user->id,
        type: 'test_notification',
        title: 'Test Title',
        message: 'Test message content',
        link: '/test',
    );

    expect($notification)->toBeInstanceOf(Notification::class);
    expect($notification->user_id)->toBe($this->user->id);
    expect($notification->type)->toBe('test_notification');
    expect($notification->title)->toBe('Test Title');
    expect($notification->message)->toBe('Test message content');
    expect($notification->link)->toBe('/test');
    expect($notification->is_read)->toBeFalse();
});

test('sends notification without optional fields', function () {
    $notification = $this->action->execute(
        userId: $this->user->id,
        type: 'simple',
        title: 'Simple',
    );

    expect($notification->message)->toBeNull();
    expect($notification->link)->toBeNull();
});

test('throws exception for non-existent user', function () {
    expect(fn () => $this->action->execute(
        userId: 'non-existent-id',
        type: 'test',
        title: 'Test',
    ))->toThrow(ModelNotFoundException::class);
});
