<?php

declare(strict_types=1);

use App\Support\PiiMasker;

it('masks password fields completely', function () {
    $data = ['password' => 'secret123', 'password_confirmation' => 'secret123'];

    $masked = PiiMasker::maskArray($data);

    expect($masked['password'])->toBe('***');
    expect($masked['password_confirmation'])->toBe('***');
});

it('masks token fields completely', function () {
    $data = ['token' => 'abc123', 'api_token' => 'xyz789'];

    $masked = PiiMasker::maskArray($data);

    expect($masked['token'])->toBe('***');
    expect($masked['api_token'])->toBe('***');
});

it('partially masks email', function () {
    $email = 'user@example.com';

    $masked = PiiMasker::maskValue('email', $email);

    expect($masked)->toBe('us***@example.com');
});

it('handles short email local part', function () {
    $email = 'ab@example.com';

    $masked = PiiMasker::maskValue('email', $email);

    expect($masked)->toBe('a***@example.com');
});

it('returns masked for email without @', function () {
    $masked = PiiMasker::maskValue('email', 'invalid');

    expect($masked)->toBe('***');
});

it('partially masks phone number', function () {
    $phone = '081234567890';

    $masked = PiiMasker::maskValue('phone', $phone);

    expect($masked)->toBe('********7890');
});

it('returns masked for short phone', function () {
    $masked = PiiMasker::maskValue('phone', '123');

    expect($masked)->toBe('***');
});

it('masks name partially', function () {
    $name = 'John Doe';

    $masked = PiiMasker::maskValue('name', $name);

    expect($masked)->toBe('J. Doe');
});

it('handles single word name', function () {
    $name = 'John';

    $masked = PiiMasker::maskValue('name', $name);

    expect($masked)->toBe('J***');
});

it('passes through unknown keys like username', function () {
    $username = 'johndoe';

    $masked = PiiMasker::maskValue('username', $username);

    expect($masked)->toBe('johndoe');
});

it('passes through short unknown keys', function () {
    $username = 'jo';

    $masked = PiiMasker::maskValue('username', $username);

    expect($masked)->toBe('jo');
});

it('masks nested arrays', function () {
    $data = [
        'user' => [
            'email' => 'test@example.com',
            'password' => 'secret',
        ],
    ];

    $masked = PiiMasker::maskArray($data);

    expect($masked['user']['email'])->toBe('te***@example.com');
    expect($masked['user']['password'])->toBe('***');
});

it('masks authorization header', function () {
    $masked = PiiMasker::maskValue('authorization', 'Bearer token123');

    expect($masked)->toBe('***');
});

it('masks credit card number', function () {
    $masked = PiiMasker::maskValue('credit_card', '1234567812345678');

    expect($masked)->toBe('***');
});
