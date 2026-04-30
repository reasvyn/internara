<?php

declare(strict_types=1);

namespace Modules\Shared\Tests\Unit\Support;

use Modules\Shared\Support\Formatter;

describe('Formatter Utility', function () {
    test('it normalizes path segments', function () {
        expect(Formatter::path('test', 'path'))
            ->toBe('test/path')
            ->and(Formatter::path('/test/', '/path/'))
            ->toBe('test/path')
            ->and(Formatter::path('test//path'))
            ->toBe('test/path');
    });

    test('it normalizes namespace segments', function () {
        expect(Formatter::namespace('Modules', 'User'))
            ->toBe('Modules\User')
            ->and(Formatter::namespace('\\Modules\\', '\\User\\'))
            ->toBe('Modules\User')
            ->and(Formatter::namespace('Modules/User'))
            ->toBe('Modules\User');
    });

    test('it formats currency as IDR', function () {
        expect(Formatter::currency(50000))
            ->toBe('Rp 50.000')
            ->and(Formatter::currency(1250500))
            ->toBe('Rp 1.250.500');
    });

    test('it formats dates in Indonesian locale', function () {
        app()->setLocale('id');
        expect(Formatter::date('2026-02-10'))->toBe('10 Februari 2026');

        app()->setLocale('en');
        expect(Formatter::date('2026-02-10'))->toBe('10 February 2026');
    });

    test('it normalizes Indonesian phone numbers', function () {
        expect(Formatter::phone('08123456789'))
            ->toBe('+628123456789')
            ->and(Formatter::phone('8123456789'))
            ->toBe('+628123456789')
            ->and(Formatter::phone('+62 812-3456-789'))
            ->toBe('+628123456789');
    });
});
