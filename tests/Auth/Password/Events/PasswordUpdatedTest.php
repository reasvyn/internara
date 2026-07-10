<?php

declare(strict_types=1);

use App\Auth\Password\Events\PasswordUpdated;
use App\User\Models\User;

test('password updated event name and payload', function () {
    $user = new class extends User {};
    $user->forceFill(['id' => 'u-1']);

    $event = new PasswordUpdated($user);

    expect($event->user->id)->toBe('u-1');
    expect($event->eventName())->toBe('password.updated');
    expect($event->toPayload())->toHaveKey('user_id');
});
