<?php

declare(strict_types=1);

namespace Modules\Shared\Tests\Unit\Support;

use Illuminate\Support\Facades\Config;
use Modules\Shared\Support\Environment;

describe('Environment', function () {
    beforeEach(function () {
        Config::set('app.debug', false);
        Config::set('app.env', 'production');
    });

    test('it detects debug mode from config', function () {
        Config::set('app.debug', true);
        expect(Environment::isDebugMode())->toBeTrue();

        Config::set('app.debug', false);
        expect(Environment::isDebugMode())->toBeFalse();
    });

    test('it detects production environment', function () {
        Config::set('app.env', 'production');
        expect(Environment::isProduction())->toBeTrue();

        Config::set('app.env', 'local');
        expect(Environment::isProduction())->toBeFalse();
    });

    test('it detects testing environment', function () {
        Config::set('app.env', 'testing');
        expect(Environment::isTesting())->toBeTrue();
    });
});
