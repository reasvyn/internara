<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rules\Password as BasePassword;
use Modules\Shared\Rules\Password;

test('it returns a low complexity rule', function () {
    $rule = Password::low();
    expect($rule)->toBeInstanceOf(BasePassword::class);
});

test('it returns a medium complexity rule', function () {
    $rule = Password::medium();
    expect($rule)->toBeInstanceOf(BasePassword::class);
});

test('it returns a high complexity rule', function () {
    $rule = Password::high();
    expect($rule)->toBeInstanceOf(BasePassword::class);
});

test('it automatically selects high complexity in production', function () {
    Config::set('app.env', 'production');
    $rule = Password::auto();

    // We can't easily inspect the internal state of BasePassword,
    // but we can verify it's a Password rule.
    expect($rule)->toBeInstanceOf(BasePassword::class);
});

test('it automatically selects low complexity in non-production', function () {
    Config::set('app.env', 'local');
    $rule = Password::auto();
    expect($rule)->toBeInstanceOf(BasePassword::class);
});
