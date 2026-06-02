<?php

declare(strict_types=1);

use App\Domain\Core\Support\PiiMasker;

describe('PiiMasker', function () {
    describe('maskArray', function () {
        it('fully masks sensitive keys', function () {
            $result = PiiMasker::maskArray([
                'password' => 'secret123',
                'token' => 'abc-token',
                'api_key' => 'key-123',
            ]);

            expect($result['password'])->toBe('***')
                ->and($result['token'])->toBe('***')
                ->and($result['api_key'])->toBe('***');
        });

        it('partially masks email', function () {
            $result = PiiMasker::maskArray(['email' => 'john.doe@example.com']);

            expect($result['email'])->toBe('jo***@example.com');
        });

        it('masks short email correctly', function () {
            $result = PiiMasker::maskArray(['email' => 'a@b.com']);

            expect($result['email'])->toBe('a***@b.com');
        });

        it('masks email without @ as full mask', function () {
            $result = PiiMasker::maskArray(['email' => 'notanemail']);

            expect($result['email'])->toBe('***');
        });

        it('partially masks phone numbers', function () {
            $result = PiiMasker::maskArray(['phone' => '081234567890']);

            expect($result['phone'])->toBe('********7890');
        });

        it('masks short phone fully', function () {
            $result = PiiMasker::maskArray(['phone' => '1234']);

            expect($result['phone'])->toBe('***');
        });

        it('partially masks name', function () {
            $result = PiiMasker::maskArray(['name' => 'John Doe']);

            expect($result['name'])->toBe('J. Doe');
        });

        it('masks single-word name', function () {
            $result = PiiMasker::maskArray(['name' => 'John']);

            expect($result['name'])->toMatch('/^J\*+/');
        });

        it('passes through non-sensitive values', function () {
            $result = PiiMasker::maskArray([
                'title' => 'Hello',
                'count' => 42,
                'active' => true,
            ]);

            expect($result['title'])->toBe('Hello')
                ->and($result['count'])->toBe(42)
                ->and($result['active'])->toBeTrue();
        });

        it('recursively masks nested arrays', function () {
            $result = PiiMasker::maskArray([
                'user' => [
                    'name' => 'Jane Doe',
                    'password' => 'secret',
                ],
            ]);

            expect($result['user']['name'])->toBe('J. Doe')
                ->and($result['user']['password'])->toBe('***');
        });

        it('handles case-insensitive key matching', function () {
            $result = PiiMasker::maskArray(['Password' => 'secret', 'EMAIL' => 'a@b.com']);

            expect($result['Password'])->toBe('***')
                ->and($result['EMAIL'])->toBe('a***@b.com');
        });

        it('masks keys containing sensitive substrings', function () {
            $result = PiiMasker::maskArray([
                'current_password' => 'secret',
                'old_password' => 'old-secret',
                'password_confirmation' => 'secret',
            ]);

            expect($result['current_password'])->toBe('***')
                ->and($result['old_password'])->toBe('***')
                ->and($result['password_confirmation'])->toBe('***');
        });
    });

    describe('maskValue', function () {
        it('fully masks single sensitive value', function () {
            expect(PiiMasker::maskValue('password', 'secret'))->toBe('***');
        });

        it('partially masks email value', function () {
            expect(PiiMasker::maskValue('email', 'test@example.com'))->toBe('te***@example.com');
        });

        it('passes through non-sensitive value', function () {
            expect(PiiMasker::maskValue('title', 'Hello'))->toBe('Hello');
        });
    });

    describe('maskIp', function () {
        it('masks IPv4 addresses', function () {
            expect(PiiMasker::maskIp('192.168.1.100'))->toBe('192.168.***.***');
        });

        it('masks IPv6 addresses', function () {
            $result = PiiMasker::maskIp('2001:db8::ff00:42:8329');

            expect($result)->toContain('::****');
        });

        it('returns null for null input', function () {
            expect(PiiMasker::maskIp(null))->toBeNull();
        });

        it('returns empty string for empty input', function () {
            expect(PiiMasker::maskIp(''))->toBe('');
        });
    });

    describe('maskUserAgent', function () {
        it('truncates long user agents', function () {
            $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

            $result = PiiMasker::maskUserAgent($ua);

            expect($result)->toEndWith('...')
                ->and(strlen($result))->toBe(53);
        });

        it('returns null for null input', function () {
            expect(PiiMasker::maskUserAgent(null))->toBeNull();
        });

        it('returns empty string for empty input', function () {
            expect(PiiMasker::maskUserAgent(''))->toBe('');
        });
    });
});
