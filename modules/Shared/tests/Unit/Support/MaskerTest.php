<?php

declare(strict_types=1);

namespace Modules\Shared\Tests\Unit\Support;

use Modules\Shared\Support\Masker;

describe('Masker Support Utility', function () {
    test('it masks email addresses', function () {
        expect(Masker::email('user@example.com'))
            ->toBe('u**r@example.com')
            ->and(Masker::email('test.account@domain.id'))
            ->toBe('t**********t@domain.id')
            ->and(Masker::email(''))
            ->toBe('');
    });

    test('it masks generic sensitive values', function () {
        expect(Masker::sensitive('password123'))
            ->toBe('pas******23')
            ->and(Masker::sensitive('08123456789'))
            ->toBe('081******89');
    });

    test('it masks arrays recursively', function () {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'security' => [
                'password' => 'secret123',
                'token' => 'plain-token',
            ],
            'meta' => [
                'phone' => '08123456789',
            ],
        ];

        $masked = Masker::maskArray($data);

        expect($masked['name'])
            ->toBe('John Doe')
            ->and($masked['email'])
            ->toBe('j**n@example.com')
            ->and($masked['security']['password'])
            ->toBe('sec****23')
            ->and($masked['security']['token'])
            ->toBe('pla******en')
            ->and($masked['meta']['phone'])
            ->toBe('081******89');
    });
});
