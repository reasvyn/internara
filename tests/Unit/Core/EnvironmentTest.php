<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Domain\Core\Support\Environment;

test('isDebugMode returns config value', function () {
    config(['app.debug' => true]);
    expect(Environment::isDebugMode())->toBeTrue();

    config(['app.debug' => false]);
    expect(Environment::isDebugMode())->toBeFalse();
});

test('isDevelopment returns true for local environment', function () {
    app()->detectEnvironment(fn () => 'local');

    expect(Environment::isDevelopment())->toBeTrue();
});

test('isDevelopment returns true for dev environment', function () {
    app()->detectEnvironment(fn () => 'dev');

    expect(Environment::isDevelopment())->toBeTrue();
});

test('isDevelopment returns false for production', function () {
    app()->detectEnvironment(fn () => 'production');

    expect(Environment::isDevelopment())->toBeFalse();
});

test('isStaging returns true for staging environment', function () {
    app()->detectEnvironment(fn () => 'staging');

    expect(Environment::isStaging())->toBeTrue();
});

test('isStaging returns false for production', function () {
    app()->detectEnvironment(fn () => 'production');

    expect(Environment::isStaging())->toBeFalse();
});

test('isTesting returns boolean', function () {
    $result = Environment::isTesting();

    expect($result)->toBeBool();
});

test('isMaintenance returns false when not in maintenance', function () {
    expect(Environment::isMaintenance())->toBeFalse();
});

test('isProduction returns true for production environment', function () {
    app()->detectEnvironment(fn () => 'production');

    expect(Environment::isProduction())->toBeTrue();
});

test('isProduction returns false for local', function () {
    app()->detectEnvironment(fn () => 'local');

    expect(Environment::isProduction())->toBeFalse();
});

test('environment class is final', function () {
    $reflection = new \ReflectionClass(Environment::class);

    expect($reflection->isFinal())->toBeTrue();
});
