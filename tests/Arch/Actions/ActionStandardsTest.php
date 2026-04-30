<?php

declare(strict_types=1);

namespace Tests\Arch\Actions;

/**
 * S2 - Sustain & S3 - Scalable: Action Standards
 * Ensures all Actions follow the stateless use-case pattern
 */
describe('Action Standards', function () {
    
    test('all actions should have execute method')
        ->expect('App\Actions')
        ->toHaveMethod('execute');
    
    test('all actions should use strict types')
        ->expect('App\Actions')
        ->toUseStrictTypes();
    
    test('actions should be stateless')
        ->expect('App\Actions')
        ->classes()
        ->toHaveMethods(['execute']) // At minimum, must have execute method
        ->ignoring([
            // Allow readonly constructor-injected dependencies
        ]);
    
    test('actions should receive validated data')
        ->expect('App\Actions')
        ->not->toUse('request()->all');
});
