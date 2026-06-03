<?php

declare(strict_types=1);

use App\Domain\Core\Support\PiiMasker;

test('PiiMasker fully masks sensitive keys', function () {
    expect(PiiMasker::maskValue('password', 'secret123'))->toBe('***');
    expect(PiiMasker::maskValue('token', 'my-api-token'))->toBe('***');
    expect(PiiMasker::maskValue('credit_card', '1234-5678'))->toBe('***');
});

test('PiiMasker partially masks email', function () {
    expect(maskEmailForTest('john@example.com'))->toBe('jo***@example.com');
    expect(maskEmailForTest('ab@domain.com'))->toBe('a***@domain.com');
    expect(maskEmailForTest('a'))->toBe('***');
});

test('PiiMasker partially masks phone', function () {
    expect(maskPhoneForTest('123456789'))->toBe('*****6789');
    expect(maskPhoneForTest('123'))->toBe('***');
});

test('PiiMasker partially masks name', function () {
    expect(maskNameForTest('John Doe'))->toBe('J. Doe');
    expect(maskNameForTest('John'))->toBe('J***');
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

// Polyfills to test private methods via public helpers if needed, but wait!
// PiiMasker uses self::{$method}((string) $value) inside maskValue() when key matches the partial mask keys.
// So we can call maskValue() instead of calling the private methods directly!
// Let's modify the email/phone/name tests to use maskValue()!
class PiiMaskerTestHelper
{
    public static function maskEmail(string $val)
    {
        return PiiMasker::maskValue('email', $val);
    }

    public static function maskPhone(string $val)
    {
        return PiiMasker::maskValue('phone', $val);
    }

    public static function maskName(string $val)
    {
        return PiiMasker::maskValue('name', $val);
    }
}

function maskEmailForTest(string $val)
{
    return PiiMaskerTestHelper::maskEmail($val);
}
function maskPhoneForTest(string $val)
{
    return PiiMaskerTestHelper::maskPhone($val);
}
function maskNameForTest(string $val)
{
    return PiiMaskerTestHelper::maskName($val);
}
