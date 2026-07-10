<?php

declare(strict_types=1);

use App\Core\Contracts\SendsNotifications;
use App\Core\Contracts\SettingsStore;
use App\User\Notifications\Actions\SendNotificationAction;
use Illuminate\Support\Facades\RateLimiter;

test('registers sends notifications binding', function () {
    $instance = app(SendsNotifications::class);

    expect($instance)->toBeInstanceOf(SendNotificationAction::class);
});

test('registers settings store singleton', function () {
    $first = app(SettingsStore::class);
    $second = app(SettingsStore::class);

    expect($first === $second)->toBeTrue();
});

test('settings store delegates to settings facade', function () {
    $store = app(SettingsStore::class);

    expect($store->get('non_existent_key'))->toBeNull();
    expect($store->get('non_existent_key', 'fallback'))->toBe('fallback');
});

test('registers admin rate limiter', function () {
    $limiter = RateLimiter::limiter('admin');

    expect($limiter)->not->toBeNull();
});

test('registers global rate limiter', function () {
    $limiter = RateLimiter::limiter('global');

    expect($limiter)->not->toBeNull();
});
