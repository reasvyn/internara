<?php

declare(strict_types=1);

namespace Modules\Shared\Tests\Unit\Functions;

use Illuminate\Support\Facades\Config;

describe('Shared Global Helpers', function () {
    test('is_debug_mode returns correct state', function () {
        Config::set('app.debug', true);
        expect(is_debug_mode())->toBeTrue();

        Config::set('app.debug', false);
        expect(is_debug_mode())->toBeFalse();
    });

    test('is_testing returns true during tests', function () {
        expect(is_testing())->toBeTrue();
    });

    test('is_development detects local environment', function () {
        app()->detectEnvironment(fn () => 'local');
        expect(is_development())->toBeTrue();

        app()->detectEnvironment(fn () => 'production');
        expect(is_development())->toBeFalse();
    });

    test('is_active_module returns false for non-existent modules', function () {
        expect(is_active_module('NonExistent'))->toBeFalse();
    });

    test('shared_url generates correct paths', function () {
        expect(shared_url('test'))->toContain('/shared/test');
    });
});
