<?php

declare(strict_types=1);

use App\Core\Support\PiiMasker;

test('maskArray fully masks sensitive keys', function () {
    $result = PiiMasker::maskArray([
        'password' => 'secret123',
        'access_token' => 'tok_abc',
        'birth_date' => '1990-01-01',
        'dob' => '1990-01-01',
        'address' => '123 Main St',
        'passport' => 'AB123456',
        'driver_license' => 'DL123456',
        'drivers_license' => 'DL123456',
        'security_question' => 'Mother maiden',
        'security_answer' => 'Smith',
        'bank_account' => '1234567890',
        'account_number' => '1234567890',
        'routing_number' => '123456789',
        'health_insurance' => 'HI-12345',
        'medical_record' => 'MR-98765',
        'old_password' => 'secret',
    ]);

    expect($result['password'])->toBe('***');
    expect($result['access_token'])->toBe('***');
    expect($result['birth_date'])->toBe('***');
    expect($result['dob'])->toBe('***');
    expect($result['address'])->toBe('***');
    expect($result['passport'])->toBe('***');
    expect($result['driver_license'])->toBe('***');
    expect($result['drivers_license'])->toBe('***');
    expect($result['security_question'])->toBe('***');
    expect($result['security_answer'])->toBe('***');
    expect($result['bank_account'])->toBe('***');
    expect($result['account_number'])->toBe('***');
    expect($result['routing_number'])->toBe('***');
    expect($result['health_insurance'])->toBe('***');
    expect($result['medical_record'])->toBe('***');
    expect($result['old_password'])->toBe('***');
    expect($result['regular_key'] ?? 'absent')->toBe('absent');
});

test('maskArray partially masks email phone and name', function () {
    $result = PiiMasker::maskArray([
        'email' => 'john@example.com',
        'phone' => '08123456789',
        'name' => 'John Doe',
    ]);

    expect($result['email'])->toBe('jo***@example.com');
    expect($result['phone'])->toBe('*******6789');
    expect($result['name'])->toBe('J. Doe');
});

test('maskArray handles nested structures and edge cases', function () {
    $nested = PiiMasker::maskArray([
        'user' => [
            'password' => 'secret',
            'email' => 'a@b.com',
        ],
    ]);
    expect($nested['user']['password'])->toBe('***');
    expect($nested['user']['email'])->toBe('a***@b.com');

    $mixed = PiiMasker::maskArray([
        'user' => [
            'profile' => ['password' => 'secret', 'email' => 'user@example.com'],
            'metadata' => 'visible',
        ],
    ]);
    expect($mixed['user']['profile']['password'])->toBe('***');
    expect($mixed['user']['profile']['email'])->toBe('us***@example.com');
    expect($mixed['user']['metadata'])->toBe('visible');

    expect(PiiMasker::maskArray([]))->toBe([]);

    $visible = PiiMasker::maskArray(['regular_key' => 'visible']);
    expect($visible['regular_key'])->toBe('visible');

    $nullResult = PiiMasker::maskArray([
        'password' => null,
        'email' => null,
        'name' => null,
        'regular_key' => null,
    ]);
    expect($nullResult['password'])->toBe('***');
    expect($nullResult['email'])->toBe('***');
    expect($nullResult['name'])->toBe('***');
    expect($nullResult['regular_key'])->toBeNull();

    $intKeys = PiiMasker::maskArray(['password', 'email' => 'test@example.com']);
    expect($intKeys[0])->toBe('password');
    expect($intKeys['email'])->toBe('te***@example.com');
});

test('maskValue handles all types', function () {
    expect(PiiMasker::maskValue('password', 'secret'))->toBe('***');
    expect(PiiMasker::maskValue('username', 'john'))->toBe('john');
});

test('maskValue masks email correctly', function () {
    expect(PiiMasker::maskValue('email', 'john@example.com'))->toBe('jo***@example.com');
    expect(PiiMasker::maskValue('email', 'a@b.com'))->toBe('a***@b.com');
    expect(PiiMasker::maskValue('email', 'notanemail'))->toBe('***');
});

test('maskValue masks phone correctly', function () {
    expect(PiiMasker::maskValue('phone', '+628123456789'))->toMatch('/\*{7}6789$/');
    expect(PiiMasker::maskValue('phone', '123'))->toBe('***');
});

test('maskValue masks name correctly', function () {
    expect(PiiMasker::maskValue('name', 'John Doe'))->toBe('J. Doe');
    expect(PiiMasker::maskValue('name', 'Madonna'))->toMatch('/^M\*{6}$/');
    expect(PiiMasker::maskValue('name', ''))->toBe('***');
    expect(PiiMasker::maskValue('name', 'John Michael Doe'))->toBe('J. Doe');
});

test('maskIp handles all variants', function () {
    expect(PiiMasker::maskIp('192.168.1.1'))->toBe('192.168.***.***');
    expect(PiiMasker::maskIp('2001:db8::1'))->toContain('****');
    expect(PiiMasker::maskIp('::1'))->toContain('****');
    expect(PiiMasker::maskIp('not-an-ip'))->toBe('***.***.***.***');
    expect(PiiMasker::maskIp(null))->toBeNull();
    expect(PiiMasker::maskIp(''))->toBe('');
});

test('maskUserAgent handles all variants', function () {
    $long = str_repeat('a', 100);
    $result = PiiMasker::maskUserAgent($long);
    expect($result)->toEndWith('...');
    expect(strlen($result))->toBe(53);

    expect(PiiMasker::maskUserAgent(null))->toBeNull();
    expect(PiiMasker::maskUserAgent(''))->toBe('');
    expect(PiiMasker::maskUserAgent('Mozilla/5.0'))->toBe('Mozilla/5.0...');
});
