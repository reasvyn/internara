<?php

declare(strict_types=1);

namespace Modules\Shared\Tests\Unit\Support;

use Modules\Shared\Support\Formatter;
use Modules\Shared\Support\Masker;

test('Formatter::path normalizes paths correctly', function () {
    expect(Formatter::path('test', 'path'))
        ->toBe('test/path')
        ->and(Formatter::path('/test/', '/path/'))
        ->toBe('test/path')
        ->and(Formatter::path('test//path'))
        ->toBe('test/path');
});

test('Formatter::namespace normalizes namespaces correctly', function () {
    expect(Formatter::namespace('Modules', 'User'))
        ->toBe('Modules\\User')
        ->and(Formatter::namespace('\\Modules\\', '\\User\\'))
        ->toBe('Modules\\User')
        ->and(Formatter::namespace('Modules/User'))
        ->toBe('Modules\\User');
});

test('Masker::email masks email addresses', function () {
    expect(Masker::email('user@example.com'))
        ->toBe('u***@example.com')
        ->and(Masker::email('johndoe@gmail.com'))
        ->toBe('j******@gmail.com');
});

test('Masker::sensitive masks sensitive values', function () {
    expect(Masker::sensitive('08123456789', 3, 2))
        ->toBe('081******89')
        ->and(Masker::sensitive('secret', 1, 1))
        ->toBe('s****t');
});
