<?php

declare(strict_types=1);

namespace Modules\Shared\Tests\Unit\Support;

use Modules\Shared\Support\Masker;

describe('Masker Utility', function () {
    test('it masks email addresses', function () {
        expect(Masker::email('user@example.com'))
            ->toBe('u***@example.com')
            ->and(Masker::email('johndoe@gmail.com'))
            ->toBe('j******@gmail.com');
    });

    test('it masks generic sensitive values', function () {
        expect(Masker::sensitive('08123456789', 3, 2))
            ->toBe('081******89')
            ->and(Masker::sensitive('secret', 1, 1))
            ->toBe('s****t');
    });
});
