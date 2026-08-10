<?php

declare(strict_types=1);

use App\Core\Support\Environment;

it('FR-SUP5: isProduction() reflects the current environment', function () {
    expect(Environment::isProduction())->toBe(app()->environment('production'));
});

it('FR-SUP5: isTesting() is true while running the test suite', function () {
    expect(Environment::isTesting())->toBeTrue();
});

it('FR-SUP5: isLocal() reflects the local environment', function () {
    expect(Environment::isLocal())->toBe(app()->environment('local'));
});

it('FR-SUP5: isCLI() is true while running in the console', function () {
    expect(Environment::isCLI())->toBeTrue();
});
