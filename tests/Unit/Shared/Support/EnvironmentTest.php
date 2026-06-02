<?php

declare(strict_types=1);

use App\Domain\Shared\Support\Environment;

describe('Environment', function () {
    beforeEach(function () {
        app()->forgetInstance('isDownForMaintenance');
    });

    it('detects debug mode', function () {
        config(['app.debug' => true]);
        expect(Environment::isDebugMode())->toBeTrue();

        config(['app.debug' => false]);
        expect(Environment::isDebugMode())->toBeFalse();
    });

    it('detects debug mode default is false', function () {
        config(['app.debug' => null]);
        expect(Environment::isDebugMode())->toBeFalse();
    });

    it('detects development environment', function () {
        app()->detectEnvironment(fn () => 'local');
        expect(Environment::isDevelopment())->toBeTrue();

        app()->detectEnvironment(fn () => 'dev');
        expect(Environment::isDevelopment())->toBeTrue();
    });

    it('detects non-development environment', function () {
        app()->detectEnvironment(fn () => 'production');
        expect(Environment::isDevelopment())->toBeFalse();

        app()->detectEnvironment(fn () => 'staging');
        expect(Environment::isDevelopment())->toBeFalse();

        app()->detectEnvironment(fn () => 'testing');
        expect(Environment::isDevelopment())->toBeFalse();
    });

    it('detects staging environment', function () {
        app()->detectEnvironment(fn () => 'staging');
        expect(Environment::isStaging())->toBeTrue();

        app()->detectEnvironment(fn () => 'production');
        expect(Environment::isStaging())->toBeFalse();
    });

    it('detects testing environment', function () {
        expect(Environment::isTesting())->toBeTrue();
    });

    it('detects maintenance mode', function () {
        expect(Environment::isMaintenance())->toBeFalse();
    });

    it('detects production environment', function () {
        app()->detectEnvironment(fn () => 'production');
        expect(Environment::isProduction())->toBeTrue();

        app()->detectEnvironment(fn () => 'local');
        expect(Environment::isProduction())->toBeFalse();
    });
});
