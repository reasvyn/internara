<?php

declare(strict_types=1);

use App\User\AccountStatus\Events\UserAccountLocked;
use App\User\AccountStatus\Events\UserAccountUnlocked;
use App\User\Models\User;

function makeStatusUser(string $id): User
{
    $model = new class extends User {};
    $model->forceFill(['id' => $id]);

    return $model;
}

test('user account locked event name and payload', function () {
    $event = new UserAccountLocked(makeStatusUser('u-1'));

    expect($event->user->id)->toBe('u-1');
    expect($event->eventName())->toBe('user.account_locked');
    expect($event->toPayload())->toHaveKey('user_id');
});

test('user account unlocked event name and payload', function () {
    $event = new UserAccountUnlocked(makeStatusUser('u-2'));

    expect($event->user->id)->toBe('u-2');
    expect($event->eventName())->toBe('user.account_unlocked');
    expect($event->toPayload())->toHaveKey('user_id');
});
