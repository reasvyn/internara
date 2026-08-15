<?php

declare(strict_types=1);

use App\Core\Support\Environment;

it('C8F0D-FR-SUP5: isProduction() reflects the current environment', function () {
    expect(Environment::isProduction())->toBe(app()->environment('production'));
});

it('C8F0D-FR-SUP5: isTesting() is true while running the test suite', function () {
    expect(Environment::isTesting())->toBeTrue();
});

it('C8F0D-FR-SUP5: isLocal() reflects the local environment', function () {
    expect(Environment::isLocal())->toBe(app()->environment('local'));
});

it('C8F0D-FR-SUP5: isCLI() is true while running in the console', function () {
    expect(Environment::isCLI())->toBeTrue();
});
