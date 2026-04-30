<?php

declare(strict_types=1);

namespace Tests\Arch\OptionalLayers;

/**
 * S3 - Scalable: Event Standards
 * Ensures Events are used correctly for side effects
 */
describe('Event Standards', function () {
    
    test('events should use strict types')
        ->expect('App\Events')
        ->toUseStrictTypes();
    
    test('events should use Dispatchable trait')
        ->expect('App\Events')
        ->classes()
        ->toUseTrait('Illuminate\Foundation\Events\Dispatchable');
});
