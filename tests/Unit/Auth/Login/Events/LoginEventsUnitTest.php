<?php

declare(strict_types=1);

use App\Auth\Login\Events\LoginFailed;
use App\Auth\Login\Events\LoginSucceeded;
use App\Core\Events\BaseEvent;
use App\User\Models\User;

test('login failed event carries identifier and reason', function () {
    $event = new LoginFailed(identifier: 'test@example.com', reason: 'invalid_password');

    expect($event->identifier)->toBe('test@example.com');
    expect($event->reason)->toBe('invalid_password');
});

test('login failed event returns correct event name', function () {
    $event = new LoginFailed(identifier: 'test@example.com', reason: 'invalid_password');

    expect($event->eventName())->toBe('login.failed');
});

test('login failed event extends base event', function () {
    $event = new LoginFailed(identifier: 'u', reason: 'r');

    expect($event)->toBeInstanceOf(BaseEvent::class);
});

test('login succeeded event carries user and identifier', function () {
    $user = new User(['id' => 'uuid-1', 'name' => 'test']);
    $event = new LoginSucceeded(user: $user, identifier: 'test@example.com');

    expect($event->user)->toBe($user);
    expect($event->identifier)->toBe('test@example.com');
});

test('login succeeded event returns correct event name', function () {
    $user = new User(['id' => 'uuid-1', 'name' => 'test']);
    $event = new LoginSucceeded(user: $user, identifier: 'test@example.com');

    expect($event->eventName())->toBe('login.succeeded');
});

test('login succeeded event extends base event', function () {
    $user = new User(['id' => 'uuid-1', 'name' => 'test']);
    $event = new LoginSucceeded(user: $user, identifier: 'u');

    expect($event)->toBeInstanceOf(BaseEvent::class);
});

test('login failed event serializes to array via toArray on base class', function () {
    $event = new LoginFailed(identifier: 'admin@test.com', reason: 'account_locked');

    expect($event->identifier)->toBe('admin@test.com');
    expect($event->reason)->toBe('account_locked');
});

test('login succeeded event uses user and identifier', function () {
    $user = new User;
    $user->forceFill(['id' => 'uuid-1', 'name' => 'test']);
    $event = new LoginSucceeded(user: $user, identifier: 'admin@test.com');

    expect($event->user->getKey())->toBe('uuid-1');
    expect($event->identifier)->toBe('admin@test.com');
});
