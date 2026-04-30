<?php

declare(strict_types=1);

namespace Tests\Arch\Services;

/**
 * S3 - Scalable: Service Standards
 * Ensures Services handle infrastructure concerns only
 */
describe('Service Standards', function () {
    
    test('services should use strict types')
        ->expect('App\Services')
        ->toUseStrictTypes();
    
    test('services should not contain business rules')
        ->expect('App\Services')
        ->not->toUse('App\Models')
        ->ignoring([
            // Allow type hints in method parameters
        ]);
    
    test('services should not use actions')
        ->expect('App\Services')
        ->not->toUse('App\Actions');
});
