<?php

declare(strict_types=1);

use App\Support\Environment;

it('detects debug mode from config', function () {
    config(['app.debug' => true]);

    expect(Environment::isDebugMode())->toBeTrue();
});

it('detects non-debug mode', function () {
    config(['app.debug' => false]);

    expect(Environment::isDebugMode())->toBeFalse();
});

it('detects development environment', function () {
    app()['env'] = 'local';

    expect(Environment::isDevelopment())->toBeTrue();
});

it('detects non-development environment', function () {
    app()['env'] = 'production';

    expect(Environment::isDevelopment())->toBeFalse();
});

it('detects staging environment', function () {
    app()['env'] = 'staging';

    expect(Environment::isStaging())->toBeTrue();
});

it('detects production environment', function () {
    app()['env'] = 'production';

    expect(Environment::isProduction())->toBeTrue();
});
