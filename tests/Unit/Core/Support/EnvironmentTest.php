<?php

declare(strict_types=1);

use App\Domain\Core\Support\Environment;
use Illuminate\Contracts\Foundation\MaintenanceMode;

test('Environment checks debug mode based on config', function () {
    config(['app.debug' => true]);
    expect(Environment::isDebugMode())->toBeTrue();

    config(['app.debug' => false]);
    expect(Environment::isDebugMode())->toBeFalse();
});

test('Environment checks testing mode correctly', function () {
    expect(Environment::isTesting())->toBeTrue();
});

test('Environment environment types matches framework state', function () {
    $originalEnv = app()->environment();

    app()->detectEnvironment(fn () => 'local');
    expect(Environment::isDevelopment())->toBeTrue();
    expect(Environment::isProduction())->toBeFalse();
    expect(Environment::isStaging())->toBeFalse();

    app()->detectEnvironment(fn () => 'dev');
    expect(Environment::isDevelopment())->toBeTrue();

    app()->detectEnvironment(fn () => 'staging');
    expect(Environment::isStaging())->toBeTrue();
    expect(Environment::isDevelopment())->toBeFalse();
    expect(Environment::isProduction())->toBeFalse();

    app()->detectEnvironment(fn () => 'production');
    expect(Environment::isProduction())->toBeTrue();
    expect(Environment::isDevelopment())->toBeFalse();
    expect(Environment::isStaging())->toBeFalse();

    app()->detectEnvironment(fn () => $originalEnv);
});

test('Environment checks maintenance mode correctly', function () {
    $maintenanceMock = Mockery::mock(MaintenanceMode::class);
    $maintenanceMock->shouldReceive('active')->andReturn(true);
    app()->instance(MaintenanceMode::class, $maintenanceMock);

    expect(Environment::isMaintenance())->toBeTrue();
});
