<?php

declare(strict_types=1);

use App\Domain\Shared\Support\Environment;

describe('Environment helper', function () {
    it('detects debug mode', function () {
        config(['app.debug' => true]);

        expect(Environment::isDebugMode())->toBeTrue();
    });

    it('detects debug mode when false', function () {
        config(['app.debug' => false]);

        expect(Environment::isDebugMode())->toBeFalse();
    });

    it('detects testing', function () {
        expect(Environment::isTesting())->toBeTrue();
    });

    it('detects production returns boolean', function () {
        expect(Environment::isProduction())->toBeBool();
    });

    it('detects development returns boolean', function () {
        expect(Environment::isDevelopment())->toBeBool();
    });

    it('detects staging returns boolean', function () {
        expect(Environment::isStaging())->toBeBool();
    });

    it('detects maintenance mode returns boolean', function () {
        expect(Environment::isMaintenance())->toBeBool();
    });

    it('is a final class', function () {
        $ref = new ReflectionClass(Environment::class);

        expect($ref->isFinal())->toBeTrue();
    });
});
