<?php

declare(strict_types=1);

use App\Domain\Shared\Support\Environment;

describe('Environment helper', function () {
    it('detects debug mode', function () {
        config(['app.debug' => true]);

        expect(Environment::isDebugMode())->toBeTrue();
    });

    it('detects production', function () {
        expect(Environment::isProduction())->toBeBool();
    });

    it('detects testing', function () {
        expect(Environment::isTesting())->toBeTrue();
    });

    it('detects maintenance mode', function () {
        expect(Environment::isMaintenance())->toBeBool();
    });

    it('detects development', function () {
        expect(Environment::isDevelopment())->toBeBool();
    });

    it('is a final class', function () {
        $ref = new ReflectionClass(Environment::class);

        expect($ref->isFinal())->toBeTrue();
    });
});
