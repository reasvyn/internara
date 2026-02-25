<?php

declare(strict_types=1);

namespace Modules\Shared\Tests\Unit\Functions;

describe('Shared Global Helpers', function () {
    test('is_debug_mode returns correct status', function () {
        config(['app.debug' => true]);
        expect(is_debug_mode())->toBeTrue();

        config(['app.debug' => false]);
        expect(is_debug_mode())->toBeFalse();
    });

    test('is_testing returns true during tests', function () {
        // In Pest/PHPUnit context, this should always be true if logic is correct
        expect(is_testing())->toBeTrue();
    });

    test('is_development detects local environment', function () {
        // We use app() detection which is more reliable in tests
        app()->detectEnvironment(fn () => 'local');
        expect(is_development())->toBeTrue();

        app()->detectEnvironment(fn () => 'production');
        expect(is_development())->toBeFalse();
        
        // Restore for other tests
        app()->detectEnvironment(fn () => 'testing');
    });

    test('is_active_module returns false for non-existent modules', function () {
        expect(is_active_module('NonExistent'))->toBeFalse();
    });

    test('shared_url generates correct paths', function () {
        expect(shared_url('test'))->toContain('/test');
    });
});
