<?php

declare(strict_types=1);

use App\Modules\Core\Support\Environment;

test('C8F0D-FR-SUP5: isProduction() reflects the current environment', function () {
    expect(Environment::isProduction())->toBe(app()->environment('production'));
});

test('C8F0D-FR-SUP5: isTesting() is true while running the test suite', function () {
    expect(Environment::isTesting())->toBeTrue();
});

test('C8F0D-FR-SUP5: isLocal() reflects the local environment', function () {
    expect(Environment::isLocal())->toBe(app()->environment('local'));
});

test('C8F0D-FR-SUP5: isCLI() is true while running in the console', function () {
    expect(Environment::isCLI())->toBeTrue();
});
