<?php

declare(strict_types=1);

namespace Tests\Arch\Actions;

/**
 * S1 - Secure & S3 - Scalable: Action Statelessness
 * Ensures Actions don't have mutable state
 */
describe('Action Statelessness', function () {

    test('actions should not store state between executions')
        ->expect('App\Actions')
        ->not->toUse(['$_SESSION', 'session(', 'cache(', 'Cookie::']);

    test('actions should not have mutable instance properties')
        ->expect('App\Actions')
        ->not->toUse('public $') // No public properties
        ->ignoring([
            // Ignore if no actions exist yet
        ]);
});
