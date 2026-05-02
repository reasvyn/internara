<?php

declare(strict_types=1);

namespace Tests\Arch\Controllers;

/**
 * S2 - Sustain: Controller Standards
 * Ensures controllers are thin and delegate to Actions
 */
describe('Controller Standards', function () {

    test('controllers should use strict types')
        ->expect('App\Http\Controllers')
        ->toUseStrictTypes();

    test('controllers should be thin (not contain business logic)')
        ->expect('App\Http\Controllers')
        ->not->toUse([
            'DB::', 'Illuminate\Support\Facades\DB',
            'validator()->make', 'Validator::make',
        ])
        ->ignoring(['App\Http\Controllers\Controller']);

    test('controllers should not have business logic methods')
        ->expect('App\Http\Controllers')
        ->classes()
        ->not->toHaveMethod('calculate') // Example business logic method
        ->ignoring(['App\Http\Controllers\Controller']);
});
