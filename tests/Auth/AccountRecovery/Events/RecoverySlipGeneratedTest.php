<?php

declare(strict_types=1);

use App\Auth\AccountRecovery\Events\RecoverySlipGenerated;
use App\User\Models\User;

test('recovery slip generated carries user and code count', function () {
    $user = new class extends User {};
    $user->forceFill(['id' => 'u-1']);

    $event = new RecoverySlipGenerated($user, codeCount: 5);

    expect($event->user->id)->toBe('u-1');
    expect($event->codeCount)->toBe(5);
    expect($event->eventName())->toBe('auth.recovery_slip_generated');
    expect($event->toPayload())->toHaveKey('user_id');
    expect($event->toPayload())->toHaveKey('codeCount');
    expect($event->toPayload()['codeCount'])->toBe(5);
});
