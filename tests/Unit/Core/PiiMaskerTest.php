<?php

declare(strict_types=1);

use App\Domain\Core\Support\PiiMasker;

describe('PiiMasker', function () {
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

    it('fully masks sensitive key substrings', function () {
        $result = PiiMasker::maskArray([
            'api_token' => 'abc',
            'access_token' => 'def',
            'old_password' => 'ghi',
        ]);

        expect($result['api_token'])->toBe('***')
            ->and($result['access_token'])->toBe('***')
            ->and($result['old_password'])->toBe('***');
    });

    it('partially masks email', function () {
        $result = PiiMasker::maskArray(['email' => 'john.doe@example.com']);

        expect($result['email'])->toContain('***')
            ->toContain('@example.com')
            ->not->toContain('john.doe');
    });

    it('partially masks phone numbers', function () {
        $result = PiiMasker::maskArray(['phone' => '+1234567890']);

        expect($result['phone'])->toEndWith('7890')
            ->and(strlen($result['phone']))->toBe(strlen('+1234567890'));
    });

    it('partially masks names', function () {
        $result = PiiMasker::maskArray(['name' => 'John Doe']);

        expect($result['name'])->toStartWith('J')
            ->toEndWith('Doe');
    });

    it('returns maskValue for fully masked keys', function () {
        expect(PiiMasker::maskValue('password', 'secret'))->toBe('***');
    });

    it('returns maskValue for partially masked keys', function () {
        $masked = PiiMasker::maskValue('email', 'test@example.com');

        expect($masked)->not->toBe('test@example.com')
            ->toContain('@example.com');
    });

    it('returns original value for non-sensitive keys', function () {
        expect(PiiMasker::maskValue('name', 'Alice'))->not->toBe('***');
    });

    it('passes through non-sensitive keys unchanged', function () {
        $result = PiiMasker::maskArray([
            'name' => 'Alice',
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
            ->and($result['user']['email'])->not->toBe('alice@test.com');
    });
});
