<?php

declare(strict_types=1);

use App\User\Profile\Events\ProfileUpdated;
use App\User\Profile\Models\Profile;

test('has event name profile.updated', function () {
    $profile = mock(Profile::class);

    $event = new ProfileUpdated($profile);

    expect($event->eventName())->toBe('profile.updated');
});

test('exposes profile publicly', function () {
    $profile = mock(Profile::class);

    $event = new ProfileUpdated($profile);

    expect($event->profile)->toBe($profile);
});
