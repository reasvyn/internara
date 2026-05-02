<?php

declare(strict_types=1);

namespace Tests\Arch\Models;

/**
 * S1 - Secure & S3 - Scalable: Model Standards
 * Ensures all models follow the standards defined in docs/standards.md
 */
describe('Model Standards', function () {

    test('all models should use HasUuid trait')
        ->expect('App\Models')
        ->classes()
        ->toUseTrait('App\Models\Concerns\HasUuid')
        ->ignoring([
            'App\Models\Concerns', // Ignore the trait itself
        ]);

    test('all models should use strict types')
        ->expect('App\Models')
        ->toUseStrictTypes();

    test('models should not send notifications directly')
        ->expect('App\Models')
        ->not->toUse('Illuminate\Support\Facades\Notification');

    test('models should not call external services')
        ->expect('App\Models')
        ->not->toUse(['Http::', 'Guzzle', 'Illuminate\Support\Facades\Http']);

    test('models should not have public properties')
        ->expect('App\Models')
        ->not->toUse('public $'); // No public properties
});
