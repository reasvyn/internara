<?php

declare(strict_types=1);

use App\Modules\Core\Support\PiiMasker;

test('89SRA-FR-PM2: maskArray() fully masks sensitive keys regardless of case', function () {
    $result = PiiMasker::maskArray(['PASSWORD' => 'secret', 'access_token' => 'tok']);

    expect($result['PASSWORD'])->toBe('***');
    expect($result['access_token'])->toBe('***');
});

test('89SRA-FR-PM1: maskArray() recursively masks nested arrays', function () {
    $result = PiiMasker::maskArray([
        'user' => ['email' => 'john.doe@example.com', 'password' => 'x'],
        'meta' => ['pin' => '1234'],
    ]);

    expect($result['user']['email'])->toBe('jo***@example.com');
    expect($result['user']['password'])->toBe('***');
    expect($result['meta']['pin'])->toBe('1234');
});

test('89SRA-FR-PM3: maskValue() partially masks emails preserving the first characters and domain', function () {
    expect(PiiMasker::maskValue('email', 'john.doe@example.com'))->toBe('jo***@example.com');
    expect(PiiMasker::maskValue('email', 'a@example.com'))->toBe('a***@example.com');
});

test('89SRA-FR-PM4: maskValue() partially masks phone numbers preserving the last 4 digits', function () {
    expect(PiiMasker::maskValue('phone', '081234567890'))->toBe('********7890');
});

test('89SRA-FR-PM5: maskValue() partially masks names preserving the first initial and last name', function () {
    expect(PiiMasker::maskValue('name', 'John Smith'))->toBe('J. Smith');
    expect(PiiMasker::maskValue('name', 'Jane'))->toBe('J***');
});

test('89SRA-FR-PM6: maskIp() masks IPv4 addresses keeping the first two octets', function () {
    expect(PiiMasker::maskIp('192.168.10.5'))->toBe('192.168.***.***');
});

test('89SRA-FR-PM7: maskIp() masks IPv6 addresses keeping only the first segment', function () {
    expect(PiiMasker::maskIp('2001:db8:85a3::8a2e'))->toBe('2001::****');
});

test('89SRA-FR-PM8: maskUserAgent() truncates to 50 characters with an ellipsis', function () {
    $ua = str_repeat('a', 60);

    expect(PiiMasker::maskUserAgent($ua))->toBe(str_repeat('a', 50).'...');
    expect(PiiMasker::maskUserAgent(null))->toBeNull();
});

test('89SRA-FR-PM9: all required sensitive keys resolve to a full mask', function () {
    foreach (['password', 'token', 'secret', 'api_key', 'credit_card', 'ssn', 'national_id', 'health_insurance'] as $key) {
        expect(PiiMasker::maskValue($key, 'anything'))->toBe('***');
    }
});

test('89SRA-FR-PM1: maskValue() leaves non-sensitive keys untouched', function () {
    expect(PiiMasker::maskValue('username', 'admin'))->toBe('admin');
});
