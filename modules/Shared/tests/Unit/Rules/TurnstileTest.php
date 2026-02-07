<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Modules\Shared\Rules\Turnstile;

test('it passes when turnstile is successful', function () {
    Config::set('services.cloudflare.turnstile.secret_key', 'test-secret');

    Http::fake([
        'challenges.cloudflare.com/*' => Http::response(['success' => true]),
    ]);

    $rule = new Turnstile;
    $passed = true;

    $rule->validate('turnstile', 'test-token', function ($message) use (&$passed) {
        $passed = false;
    });

    expect($passed)->toBeTrue();
});

test('it fails when turnstile is unsuccessful', function () {
    Config::set('services.cloudflare.turnstile.secret_key', 'test-secret');

    Http::fake([
        'challenges.cloudflare.com/*' => Http::response(['success' => false]),
    ]);

    $rule = new Turnstile;
    $errorMessage = null;

    $rule->validate('turnstile', 'test-token', function ($message) use (&$errorMessage) {
        $errorMessage = $message;
    });

    expect($errorMessage)->not->toBeNull();
});

test('it passes if secret key is missing', function () {
    Config::set('services.cloudflare.turnstile.secret_key', null);

    $rule = new Turnstile;
    $passed = true;

    $rule->validate('turnstile', 'test-token', function ($message) use (&$passed) {
        $passed = false;
    });

    expect($passed)->toBeTrue();
});
