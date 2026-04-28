<?php

declare(strict_types=1);

namespace Modules\Shared\Tests\Unit\Support;

use Modules\Shared\Support\Result;

describe('Result', function () {
    test('it creates successful result with data', function () {
        $result = Result::success(['id' => 1], 'success.message');

        expect($result->success)->toBeTrue();
        expect($result->data)->toBe(['id' => 1]);
        expect($result->message)->toBe('success.message');
    });

    test('it creates failed result with message', function () {
        $result = Result::failure('error.not-found');

        expect($result->success)->toBeFalse();
        expect($result->data)->toBeNull();
        expect($result->message)->toBe('error.not-found');
    });

    test('it includes metadata', function () {
        $result = Result::success(null, null, ['timestamp' => now()]);

        expect($result->meta)->toHaveKey('timestamp');
    });

    test('it provides fluent data access', function () {
        $result = Result::success('data');

        expect($result->data)->toBe('data');
    });
});