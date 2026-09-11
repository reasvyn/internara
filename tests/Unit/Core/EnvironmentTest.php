<?php

declare(strict_types=1);

use App\Modules\Core\Support\Environment;

describe('C8F0D: environment predicates', function (): void {
    test('C8F0D-FR-UTIL-004: the test runtime identifies itself honestly', function (): void {
        expect(Environment::isTesting())->toBeTrue();
        expect(Environment::isCLI())->toBeTrue();
        expect(Environment::isProduction())->toBeFalse();
        expect(Environment::isStaging())->toBeFalse();
        expect(Environment::isLocal())->toBeFalse();
        expect(Environment::isDevelopment())->toBeFalse();
        expect(Environment::isMaintenance())->toBeFalse();
    });

    test('C8F0D-FR-UTIL-004: debug mode mirrors application config', function (): void {
        expect(Environment::isDebugMode())->toBe((bool) config('app.debug'));
    });
});
