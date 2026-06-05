<?php

declare(strict_types=1);

use App\Support\Environment;

test('isDebugMode checks app.debug config', function () {
    config(['app.debug' => true]);
    expect(Environment::isDebugMode())->toBeTrue();

    config(['app.debug' => false]);
    expect(Environment::isDebugMode())->toBeFalse();
});

test('isDevelopment checks local or dev environment', function () {
    app()->detectEnvironment(fn () => 'local');
    expect(Environment::isDevelopment())->toBeTrue();

    app()->detectEnvironment(fn () => 'dev');
    expect(Environment::isDevelopment())->toBeTrue();

    app()->detectEnvironment(fn () => 'production');
    expect(Environment::isDevelopment())->toBeFalse();
});

test('isProduction checks production environment', function () {
    app()->detectEnvironment(fn () => 'production');
    expect(Environment::isProduction())->toBeTrue();

    app()->detectEnvironment(fn () => 'local');
    expect(Environment::isProduction())->toBeFalse();
});

test('isStaging checks staging environment', function () {
    app()->detectEnvironment(fn () => 'staging');
    expect(Environment::isStaging())->toBeTrue();

    app()->detectEnvironment(fn () => 'production');
    expect(Environment::isStaging())->toBeFalse();
});

test('isTesting returns true during unit tests', function () {
    expect(Environment::isTesting())->toBeTrue();
});

test('isMaintenance checks maintenance mode', function () {
    expect(Environment::isMaintenance())->toBeFalse();
});
