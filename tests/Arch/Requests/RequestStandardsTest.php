<?php

declare(strict_types=1);

namespace Tests\Arch\Requests;

/**
 * S1 - Secure: Form Request Standards
 * Ensures all HTTP input is properly validated
 */
describe('Form Request Standards', function () {

    test('requests should use strict types')
        ->expect('App\Http\Requests')
        ->toUseStrictTypes();

    test('requests should extend FormRequest')
        ->expect('App\Http\Requests')
        ->classes()
        ->toExtend('Illuminate\Foundation\Http\FormRequest');

    test('requests should have rules method')
        ->expect('App\Http\Requests')
        ->classes()
        ->toHaveMethod('rules');

    test('requests should have authorize method')
        ->expect('App\Http\Requests')
        ->classes()
        ->toHaveMethod('authorize');
});
