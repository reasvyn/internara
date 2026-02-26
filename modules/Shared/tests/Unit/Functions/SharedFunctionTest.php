<?php

declare(strict_types=1);

namespace Modules\Shared\Tests\Unit\Functions;

use Tests\TestCase;

describe('Shared Global Helpers', function () {
    // We use the 'pest()->extend(TestCase::class)' in Pest.php, 
    // but individual files can also define their context.

    test('it detects debug mode correctly', function () {
        config(['app.debug' => true]);
        expect(is_debug_mode())->toBeTrue();

        config(['app.debug' => false]);
        expect(is_debug_mode())->toBeFalse();
    });

    test('it identifies the testing environment', function () {
        expect(is_testing())->toBeTrue();
    });

    test('it detects development environment', function () {
        app()->detectEnvironment(fn () => 'local');
        expect(is_development())->toBeTrue();

        app()->detectEnvironment(fn () => 'production');
        expect(is_development())->toBeFalse();
        
        // Restore for cleanup
        app()->detectEnvironment(fn () => 'testing');
    });

    test('it validates module activity without exceptions', function () {
        expect(is_active_module('NonExistentModuleXYZ'))->toBeFalse();
    });

    test('it generates standardized shared urls', function () {
        $path = 'assets/test.js';
        $url = shared_url($path);
        
        expect($url)->toContain($path);
    });
});
