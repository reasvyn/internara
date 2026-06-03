<?php

declare(strict_types=1);

use App\Domain\Core\Support\PiiMasker;

test('PiiMasker fully masks sensitive keys', function () {
    expect(PiiMasker::maskValue('password', 'secret123'))->toBe('***');
    expect(PiiMasker::maskValue('token', 'my-api-token'))->toBe('***');
    expect(PiiMasker::maskValue('credit_card', '1234-5678'))->toBe('***');
});

test('PiiMasker partially masks email', function () {
    expect(PiiMasker::maskValue('email', 'john@example.com'))->toBe('jo***@example.com');
    expect(PiiMasker::maskValue('email', 'ab@domain.com'))->toBe('a***@domain.com');
    expect(PiiMasker::maskValue('email', 'a'))->toBe('***');
});

test('PiiMasker partially masks phone', function () {
    expect(PiiMasker::maskValue('phone', '123456789'))->toBe('*****6789');
    expect(PiiMasker::maskValue('phone', '123'))->toBe('***');
});

test('PiiMasker partially masks name', function () {
    expect(PiiMasker::maskValue('name', 'John Doe'))->toBe('J. Doe');
    expect(PiiMasker::maskValue('name', 'John'))->toBe('J***');
});

test('PiiMasker masks IP addresses correctly', function () {
    expect(PiiMasker::maskIp('192.168.1.100'))->toBe('192.168.***.***');
    expect(PiiMasker::maskIp('2001:db8:85a3::8a2e:370:7334'))->toBe('2001::****');
    expect(PiiMasker::maskIp(null))->toBeNull();
});

test('PiiMasker truncates long user agents', function () {
    $ua = str_repeat('A', 100);
    expect(PiiMasker::maskUserAgent($ua))->toBe(str_repeat('A', 50).'...');
    expect(PiiMasker::maskUserAgent(null))->toBeNull();
});

test('PiiMasker masks arrays recursively', function () {
    $data = [
        'name' => 'John Doe',
        'password' => 'secret',
        'nested' => [
            'email' => 'john@example.com',
            'unrelated' => 'some-data',
        ],
    ];
    $masked = PiiMasker::maskArray($data);
    expect($masked['name'])->toBe('J. Doe');
    expect($masked['password'])->toBe('***');
    expect($masked['nested']['email'])->toBe('jo***@example.com');
    expect($masked['nested']['unrelated'])->toBe('some-data');
});
