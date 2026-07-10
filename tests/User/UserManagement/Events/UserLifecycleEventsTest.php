<?php

declare(strict_types=1);

use App\User\Models\User;
use App\User\UserManagement\Events\UserCreated;
use App\User\UserManagement\Events\UserDeleted;
use App\User\UserManagement\Events\UserStatusChanged;
use App\User\UserManagement\Events\UserUpdated;

function makeEventUser(string $id): User
{
    $user = new class extends User {};
    $user->forceFill(['id' => $id]);

    return $user;
}

test('user created event has user payload', function () {
    $event = new UserCreated(makeEventUser('u-1'));

    expect($event->user->id)->toBe('u-1');
    expect($event->eventName())->toBe('user.created');
    expect($event->toPayload())->toHaveKey('user_id');
});

test('user updated event has user payload', function () {
    $event = new UserUpdated(makeEventUser('u-2'));

    expect($event->user->id)->toBe('u-2');
    expect($event->eventName())->toBe('user.updated');
});

test('user deleted event has user payload', function () {
    $event = new UserDeleted(makeEventUser('u-3'));

    expect($event->user->id)->toBe('u-3');
    expect($event->eventName())->toBe('user.deleted');
});

test('user status changed event has user', function () {
    $event = new UserStatusChanged(makeEventUser('u-4'));

    expect($event->user->id)->toBe('u-4');
    expect($event->eventName())->toBe('user.status_changed');
});
