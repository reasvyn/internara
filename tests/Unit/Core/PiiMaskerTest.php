<?php

declare(strict_types=1);

use App\Domain\Core\Support\PiiMasker;

describe('PiiMasker', function () {
    describe('maskArray', function () {
        it('fully masks sensitive keys', function () {
            $result = PiiMasker::maskArray([
                'password' => 'secret123',
                'token' => 'abc123',
                'credit_card' => '4111111111111111',
                'ssn' => '123-45-6789',
            ]);

            expect($result['password'])->toBe('***')
                ->and($result['token'])->toBe('***')
                ->and($result['credit_card'])->toBe('***')
                ->and($result['ssn'])->toBe('***');
        });

        it('masks key substrings', function () {
            $result = PiiMasker::maskArray([
                'api_token' => 'abc',
                'old_password' => 'ghi',
            ]);

            expect($result['api_token'])->toBe('***')
                ->and($result['old_password'])->toBe('***');
        });

        it('partially masks email, phone, and name', function () {
            $result = PiiMasker::maskArray([
                'email' => 'john.doe@example.com',
                'phone' => '+1234567890',
                'name' => 'John Doe',
            ]);

            expect($result['email'])->toContain('@example.com')
                ->not->toContain('john.doe');
            expect($result['phone'])->toEndWith('7890');
            expect($result['name'])->toStartWith('J')->toEndWith('Doe');
        });

        it('passes non-sensitive keys unchanged', function () {
            $result = PiiMasker::maskArray([
                'role' => 'admin',
                'active' => true,
            ]);

            expect($result['role'])->toBe('admin')
                ->and($result['active'])->toBeTrue();
        });

        it('recursively masks nested arrays', function () {
            $result = PiiMasker::maskArray([
                'user' => [
                    'email' => 'alice@test.com',
                    'password' => 'hunter2',
                ],
            ]);

            expect($result['user']['password'])->toBe('***')
                ->and($result['user']['email'])->toContain('@test.com');
        });
    });

    describe('maskValue', function () {
        it('fully masks sensitive keys', function () {
            expect(PiiMasker::maskValue('password', 'secret'))->toBe('***');
        });

        it('partially masks email', function () {
            expect(PiiMasker::maskValue('email', 'test@example.com'))
                ->toContain('@example.com')
                ->not->toBe('test@example.com');
        });

        it('passes through non-sensitive keys', function () {
            expect(PiiMasker::maskValue('role', 'admin'))->toBe('admin');
        });
    });

    describe('maskIp', function () {
        it('masks IPv4 address', function () {
            expect(PiiMasker::maskIp('192.168.1.1'))->toBe('192.168.***.***');
        });

        it('returns null for null input', function () {
            expect(PiiMasker::maskIp(null))->toBeNull();
        });
    });

    describe('maskUserAgent', function () {
        it('truncates long user agents', function () {
            $ua = str_repeat('a', 100);

            expect(PiiMasker::maskUserAgent($ua))->toEndWith('...');
        });

        it('returns null for null input', function () {
            expect(PiiMasker::maskUserAgent(null))->toBeNull();
        });
    });
});
